<?php

namespace CristalWindows\BreakdanceFormPdf;

use Breakdance\Forms\Actions\Action;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Standalone Breakdance form action: "Send PDF".
 *
 * On submission it builds a branded PDF containing only the fields relevant to
 * the selected Request Reason and emails it to a fixed recipient, with a short
 * summary in the body and any uploaded files attached alongside the PDF.
 *
 * This action does not touch the separate "Conditional Email" action.
 */
class SendPdfAction extends Action
{
    /**
     * Label shown in the Form Builder "Actions After Submission" dropdown.
     */
    public function name(): string
    {
        return 'Send PDF';
    }

    /**
     * Unique action identifier.
     */
    public function slug(): string
    {
        return 'cristal_send_pdf';
    }

    /**
     * Run on form submission.
     *
     * @param array $form     Field definitions + submitted values.
     * @param array $settings Form/action settings from the builder.
     * @param array $extra    Context: 'fields' (id => value), 'files', 'formId', etc.
     * @return array{type:string,message:string}
     */
    public function run($form, $settings, $extra): array
    {
        try {
            $fields = $this->collectFields($form, $extra);
            $reason = isset($fields['request_reason']) ? trim((string) $fields['request_reason']) : '';

            // Order + filter the values for this request reason.
            $order  = FieldConfig::orderForReason($reason);
            $labels = [];
            $values = [];
            foreach ($order as $fieldId) {
                $labels[$fieldId] = FieldConfig::label($fieldId);
                $values[$fieldId] = $this->formatValue($fieldId, $fields[$fieldId] ?? '');
            }

            // Build the PDF.
            $builder = new PdfBuilder();
            $pdfPath = $builder->render($reason, $values, $labels);

            // Gather attachments: PDF + any uploaded files.
            $attachments = array_merge([$pdfPath], $this->collectUploadedFiles($extra));

            $sent = $this->sendEmail($reason, $fields, $attachments);

            // Clean up the generated PDF (uploaded files are managed by Breakdance).
            $builder->cleanup($pdfPath);

            if (!$sent) {
                return $this->fail('PDF email could not be sent (wp_mail returned false).');
            }

            return ['type' => 'success', 'message' => 'PDF generated and emailed.'];
        } catch (\Throwable $e) {
            return $this->fail('PDF action error: ' . $e->getMessage());
        }
    }

    /**
     * Build the email and dispatch it via wp_mail.
     *
     * @param array        $fields      All submitted field values (id => value).
     * @param array<string> $attachments Absolute file paths.
     */
    private function sendEmail(string $reason, array $fields, array $attachments): bool
    {
        $to = $this->recipient();

        $fullName = trim((string) ($fields['FIRSTSURNAME'] ?? ''));
        $subjectReason = $reason !== '' ? $reason : 'Contact Form';

        $subject = sprintf('New %s%s', $subjectReason, $fullName !== '' ? ' — ' . $fullName : '');
        $subject = (string) apply_filters('cristal_bd_pdf_subject', $subject, $reason, $fields);

        $body    = $this->summaryBody($reason, $fields);
        $headers = $this->headers($fields);

        $context = [
            'to'          => $to,
            'subject'     => $subject,
            'headers'     => $headers,
            'attachments' => $attachments,
            'reason'      => $reason,
            'fields'      => $fields,
        ];
        $context = (array) apply_filters('cristal_bd_pdf_email', $context);

        return (bool) wp_mail(
            $context['to'],
            $context['subject'],
            $body,
            $context['headers'],
            $context['attachments']
        );
    }

    /**
     * Short HTML summary shown in the email body.
     */
    private function summaryBody(string $reason, array $fields): string
    {
        $rows = [
            'Request Reason' => $reason,
            'Full Name'      => (string) ($fields['FIRSTSURNAME'] ?? ''),
            'Phone Number'   => (string) ($fields['MOBTEL'] ?? ''),
        ];

        $html  = '<p>A new contact form submission has been received. '
            . 'Full details are attached as a PDF.</p>';
        $html .= '<table style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
        foreach ($rows as $label => $value) {
            $value = $value !== '' ? esc_html($value) : '&mdash;';
            $html .= '<tr>'
                . '<td style="padding:4px 12px 4px 0;font-weight:bold;color:#555;">' . esc_html($label) . ':</td>'
                . '<td style="padding:4px 0;">' . $value . '</td>'
                . '</tr>';
        }
        $html .= '</table>';

        return (string) apply_filters('cristal_bd_pdf_summary_body', $html, $reason, $fields);
    }

    /**
     * Build email headers (HTML content type + Reply-To the submitter).
     *
     * @return string[]
     */
    private function headers(array $fields): array
    {
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $email = trim((string) ($fields['email'] ?? ''));
        $name  = trim((string) ($fields['FIRSTSURNAME'] ?? ''));
        if ($email !== '' && is_email($email)) {
            $headers[] = $name !== ''
                ? sprintf('Reply-To: %s <%s>', $name, $email)
                : sprintf('Reply-To: %s', $email);
        }

        return (array) apply_filters('cristal_bd_pdf_headers', $headers, $fields);
    }

    /**
     * Resolve the fixed recipient address.
     *
     * Order of precedence:
     *   1. CRISTAL_BD_PDF_RECIPIENT constant (wp-config.php)
     *   2. `cristal_bd_pdf_recipient` filter
     *   3. Site admin email (safe fallback)
     */
    private function recipient()
    {
        $to = '';
        if (defined('CRISTAL_BD_PDF_RECIPIENT') && CRISTAL_BD_PDF_RECIPIENT) {
            $to = CRISTAL_BD_PDF_RECIPIENT;
        }
        $to = apply_filters('cristal_bd_pdf_recipient', $to);

        if (empty($to)) {
            $to = get_option('admin_email');
        }
        return $to;
    }

    /**
     * Normalise the submitted field values into an id => value map.
     *
     * Prefers $extra['fields']; falls back to reading $form entries.
     *
     * @return array<string,string>
     */
    private function collectFields($form, $extra): array
    {
        $fields = [];

        if (is_array($extra) && !empty($extra['fields']) && is_array($extra['fields'])) {
            foreach ($extra['fields'] as $id => $value) {
                $fields[$id] = $this->stringify($value);
            }
            return $fields;
        }

        // Fallback: derive from the $form structure.
        if (is_array($form)) {
            foreach ($form as $key => $field) {
                if (is_array($field) && isset($field['name'])) {
                    $fields[$field['name']] = $this->stringify($field['value'] ?? '');
                } else {
                    $fields[$key] = $this->stringify($field);
                }
            }
        }

        return $fields;
    }

    /**
     * Collect absolute paths of any uploaded files in the submission.
     *
     * The exact shape of $extra['files'] can vary, so this walks the structure
     * and keeps any value that resolves to a readable file on disk.
     *
     * @return string[]
     */
    private function collectUploadedFiles($extra): array
    {
        $paths = [];
        if (!is_array($extra) || empty($extra['files'])) {
            return $paths;
        }

        $this->walkForFiles($extra['files'], $paths);

        // De-duplicate while preserving order.
        return array_values(array_unique($paths));
    }

    /**
     * Recursively walk an array collecting readable file paths.
     *
     * @param mixed    $node
     * @param string[] $paths
     */
    private function walkForFiles($node, array &$paths): void
    {
        if (is_string($node)) {
            if ($node !== '' && @is_file($node)) {
                $paths[] = $node;
            }
            return;
        }

        if (is_array($node)) {
            // Prefer explicit path-like keys when present.
            foreach (['path', 'file', 'tmp_name', 'tmp', 'fullpath'] as $key) {
                if (isset($node[$key]) && is_string($node[$key]) && @is_file($node[$key])) {
                    $paths[] = $node[$key];
                }
            }
            foreach ($node as $child) {
                $this->walkForFiles($child, $paths);
            }
        }
    }

    /**
     * Format a field value for display in the PDF.
     */
    private function formatValue(string $fieldId, $value): string
    {
        $value = $this->stringify($value);

        if ($fieldId === 'installation_date' && $value !== '') {
            // The date input submits Y-m-d; present it in UK long form.
            $dt = \DateTime::createFromFormat('Y-m-d', $value);
            if ($dt instanceof \DateTime) {
                return $dt->format('j F Y');
            }
        }

        return $value;
    }

    /**
     * Coerce any submitted value (string/array) to a readable string.
     *
     * @param mixed $value
     */
    private function stringify($value): string
    {
        if (is_array($value)) {
            // Flatten nested values (e.g. multi-selects) to a comma list.
            $flat = [];
            array_walk_recursive($value, function ($v) use (&$flat) {
                if (is_scalar($v) && $v !== '') {
                    $flat[] = (string) $v;
                }
            });
            return implode(', ', $flat);
        }
        return trim((string) $value);
    }

    /**
     * Log a failure and return an error/success result based on strict mode.
     *
     * By default (non-strict) the visitor is not shown an error for an internal
     * PDF/email hiccup — the failure is logged for site admins instead. Enable
     * the `cristal_bd_pdf_strict_errors` filter to surface errors to Breakdance.
     *
     * @return array{type:string,message:string}
     */
    private function fail(string $message): array
    {
        error_log('[Cristal Breakdance PDF] ' . $message);

        $strict = (bool) apply_filters('cristal_bd_pdf_strict_errors', false);
        if ($strict) {
            return ['type' => 'error', 'message' => $message];
        }

        // Don't disrupt the visitor for an internal issue.
        return ['type' => 'success', 'message' => 'Submission received.'];
    }
}
