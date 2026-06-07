<?php

namespace BreakdanceFormPdf\Actions;

use Breakdance\Forms\Actions\Action;
use BreakdanceFormPdf\PdfBuilder;

use function Breakdance\Elements\control;
use function Breakdance\Elements\controlSection;
use function Breakdance\Elements\repeaterControl;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * "Send PDF" — a standalone Breakdance form action.
 *
 * On submission it evaluates a set of conditional rules (each rule = a
 * condition on a form field) to choose both the recipient and the rich-text
 * content for the document. That content is rendered to a branded PDF (mPDF)
 * and emailed, with a short summary in the body and uploaded files optionally
 * attached alongside the PDF.
 *
 * The integration mirrors the production Breakdance Forms Action API: static
 * name()/slug(), a controls() method that builds the Form Builder UI, and a
 * run($form, $settings, $extra) method. Everything is configured in the UI —
 * no field ids or branding are hardcoded.
 */
class SendPdf extends Action
{
    /**
     * Displayable label of the action (shown in the actions dropdown).
     */
    public static function name()
    {
        return 'Send PDF';
    }

    /**
     * URL-friendly unique slug. Also the key under $settings['actions'][...].
     */
    public static function slug()
    {
        return 'breakdance_form_pdf';
    }

    /**
     * Build the Form Builder controls (the UI).
     */
    public function controls()
    {
        // Reusable "populate from this form's fields" descriptor for variable
        // pickers (the "+" button) and field dropdowns.
        $fieldVariableOptions = [
            'enabled'  => true,
            'populate' => [
                'path'      => 'content.form.fields',
                'text'      => 'label',
                'value'     => 'advanced.id',
                'condition' => [
                    'path'    => 'type',
                    'operand' => 'is none of',
                    'value'   => ['file', 'html'],
                ],
            ],
        ];

        $emailFieldVariableOptions = [
            'enabled'  => true,
            'populate' => [
                'path'      => 'content.form.fields',
                'text'      => 'label',
                'value'     => 'advanced.id',
                'condition' => [
                    'path'    => 'type',
                    'operand' => 'is one of',
                    'value'   => ['email'],
                ],
            ],
        ];

        return [
            controlSection('pdf_rules', 'PDF Content & Recipient Rules', [
                repeaterControl('rules', 'Rules', [
                    control('field_id', 'Field', [
                        'type'        => 'dropdown',
                        'layout'      => 'vertical',
                        'placeholder' => 'Select a field',
                        'dropdownOptions' => [
                            'populate' => [
                                'path'  => 'content.form.fields',
                                'text'  => 'label',
                                'value' => 'advanced.id',
                            ],
                        ],
                    ]),
                    control('condition', 'Condition', [
                        'type'   => 'dropdown',
                        'layout' => 'vertical',
                        'items'  => [
                            ['text' => 'Equals', 'value' => 'equals'],
                            ['text' => 'Not Equals', 'value' => 'not_equals'],
                            ['text' => 'Contains', 'value' => 'contains'],
                            ['text' => 'Not Contains', 'value' => 'not_contains'],
                            ['text' => 'Greater Than', 'value' => 'greater_than'],
                            ['text' => 'Less Than', 'value' => 'less_than'],
                            ['text' => 'Is Set', 'value' => 'is_set'],
                            ['text' => 'Is Empty', 'value' => 'is_empty'],
                        ],
                    ]),
                    control('value', 'Value', [
                        'type'        => 'text',
                        'layout'      => 'vertical',
                        'placeholder' => 'Value to compare',
                        'condition'   => [
                            'path'    => '%%CURRENTPATH%%.condition',
                            'operand' => 'is none of',
                            'value'   => ['is_set', 'is_empty'],
                        ],
                    ]),
                    control('recipient_email', 'Send To', [
                        'type'        => 'text',
                        'layout'      => 'vertical',
                        'placeholder' => 'sales@example.com',
                    ]),
                    control('pdf_content', 'PDF Content', [
                        'type'            => 'richtext',
                        'layout'          => 'vertical',
                        'description'     => 'The content rendered into the PDF for this rule. Use the variable picker to insert field values.',
                        'variableOptions' => $fieldVariableOptions,
                        'variableItems'   => [
                            ['text' => 'All Fields', 'value' => 'all_fields'],
                        ],
                    ]),
                ], [
                    'repeaterOptions' => [
                        'titleTemplate' => 'If {field_id} {condition} → {recipient_email}',
                        'defaultTitle'  => 'Rule',
                        'buttonName'    => 'Add Rule',
                    ],
                ]),
                control('default_recipient', 'Default Recipient', [
                    'type'        => 'text',
                    'layout'      => 'vertical',
                    'placeholder' => 'info@example.com',
                    'description' => 'Fallback recipient when no rule matches.',
                ]),
                control('default_pdf_content', 'Default PDF Content', [
                    'type'            => 'richtext',
                    'layout'          => 'vertical',
                    'description'     => 'Fallback PDF content when no rule matches.',
                    'variableOptions' => $fieldVariableOptions,
                    'variableItems'   => [
                        ['text' => 'All Fields', 'value' => 'all_fields'],
                    ],
                ]),
                control('document_title', 'PDF Title', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'placeholder'     => 'Form Submission',
                    'description'     => 'Heading shown at the top of the PDF. Supports field variables.',
                    'variableOptions' => $fieldVariableOptions,
                ]),
            ]),

            controlSection('email_settings', 'Email Settings', [
                control('subject', 'Subject', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'variableOptions' => $fieldVariableOptions,
                ]),
                control('from', 'From Email', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'variableOptions' => $emailFieldVariableOptions,
                ]),
                control('from_name', 'From Name', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'variableOptions' => $fieldVariableOptions,
                ]),
                control('reply_to', 'Reply To', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'variableOptions' => $emailFieldVariableOptions,
                ]),
                control('cc', 'CC', [
                    'type'   => 'text',
                    'layout' => 'vertical',
                ]),
                control('bcc', 'BCC', [
                    'type'   => 'text',
                    'layout' => 'vertical',
                ]),
                control('body_message', 'Email Body (summary)', [
                    'type'            => 'richtext',
                    'layout'          => 'vertical',
                    'description'     => 'Short summary shown in the email body. Full details go in the attached PDF.',
                    'variableOptions' => $fieldVariableOptions,
                    'variableItems'   => [
                        ['text' => 'All Fields', 'value' => 'all_fields'],
                    ],
                ]),
                control('attach_files', 'Attach uploaded files', [
                    'type'   => 'toggle',
                    'layout' => 'inline',
                ]),
            ]),

            controlSection('pdf_appearance', 'PDF Appearance', [
                control('logo_url', 'Logo URL', [
                    'type'        => 'text',
                    'layout'      => 'vertical',
                    'placeholder' => 'https://example.com/logo.png',
                    'description' => 'Optional logo shown in the PDF header. Leave blank to show the site name instead.',
                ]),
                control('brand_colour', 'Brand Colour', [
                    'type'        => 'text',
                    'layout'      => 'vertical',
                    'placeholder' => '#2271b1',
                    'description' => 'Hex colour for the header rule and title.',
                ]),
                control('footer_text', 'Footer Text', [
                    'type'            => 'text',
                    'layout'          => 'vertical',
                    'placeholder'     => 'Leave blank to use the site name',
                    'description'     => 'Optional footer line. Supports field variables.',
                    'variableOptions' => $fieldVariableOptions,
                ]),
            ]),
        ];
    }

    /**
     * Run the action on form submission.
     *
     * @param array $form     List of field objects (each has advanced.id, label, value, type).
     * @param array $settings Form/action settings from the builder.
     * @param array $extra    Context: 'files', 'formId', etc.
     * @return array{type:string,message?:string}
     */
    public function run($form, $settings, $extra)
    {
        $s = $settings['actions'][self::slug()] ?? [];

        $rulesCfg = $s['pdf_rules'] ?? [];
        $rules    = $rulesCfg['rules'] ?? [];

        $matched = $this->evaluateRules($rules, $form);

        // Recipient: rule value, else default.
        $recipient = $matched && !empty($matched['recipient_email'])
            ? $matched['recipient_email']
            : ($rulesCfg['default_recipient'] ?? '');

        $toEmails = $this->getEmails($this->sanitize($form, $recipient));
        if (empty($toEmails)) {
            return ['type' => 'error', 'message' => 'No valid recipient (no rule matched and no default recipient set).'];
        }

        // PDF content: rule value, else default.
        $contentTpl = $matched && !empty($matched['pdf_content'])
            ? $matched['pdf_content']
            : ($rulesCfg['default_pdf_content'] ?? '');

        $contentHtml = $this->renderData($form, $contentTpl, true);
        if (trim(strip_tags($contentHtml)) === '') {
            return ['type' => 'error', 'message' => 'No PDF content configured for this submission.'];
        }

        // Document title.
        $titleTpl = $rulesCfg['document_title'] ?? '';
        $title    = $titleTpl !== '' ? trim(strip_tags($this->renderData($form, $titleTpl))) : '';
        if ($title === '') {
            $title = 'Form Submission';
        }

        // PDF appearance / branding (all UI-configurable).
        $appearance = $s['pdf_appearance'] ?? [];
        $branding   = [
            'logo'   => trim((string) ($appearance['logo_url'] ?? '')),
            'colour' => trim((string) ($appearance['brand_colour'] ?? '')),
            'footer' => $this->sanitize($form, $appearance['footer_text'] ?? ''),
        ];

        // Build the PDF. If generation fails, don't lose the notification —
        // send the email without the attachment and report it instead.
        $builder   = new PdfBuilder();
        $pdfPath   = null;
        $pdfFailed = false;
        try {
            $pdfPath = $builder->render($title, $contentHtml, $branding);
        } catch (\Throwable $e) {
            $pdfFailed = true;
            error_log('[Breakdance Form PDF] PDF generation failed; sending email without attachment: ' . $e->getMessage());
        }

        // Email settings.
        $email   = $s['email_settings'] ?? [];
        $subject = $this->renderData($form, ($email['subject'] ?? '') ?: 'New form submission');

        $fromEmail = $this->sanitize($form, ($email['from'] ?? '') ?: get_option('admin_email'));
        $fromName  = $this->sanitize($form, ($email['from_name'] ?? '') ?: get_bloginfo('name'));
        $replyTo   = $this->sanitize($form, $email['reply_to'] ?? '');
        $cc        = $this->sanitize($form, $email['cc'] ?? '');
        $bcc       = $this->sanitize($form, $email['bcc'] ?? '');

        $bodyTpl = ($email['body_message'] ?? '') !== ''
            ? $email['body_message']
            : '<p>A new form submission has been received. Full details are attached as a PDF.</p>';
        $body = $this->renderData($form, $bodyTpl, true);

        $headers = [
            "From: {$fromName} <{$fromEmail}>",
            'Content-Type: text/html; charset=UTF-8',
        ];
        if ($replyTo) {
            $headers[] = "Reply-To: {$replyTo}";
        }
        if ($cc) {
            $headers[] = "Cc: {$cc}";
        }
        if ($bcc) {
            $headers[] = "Bcc: {$bcc}";
        }

        // Attachments: the PDF (when generated), plus uploaded files when enabled.
        $attachments = [];
        if ($pdfPath !== null) {
            $attachments[] = $pdfPath;
        }
        if (!empty($email['attach_files']) && !empty($extra['files'])) {
            foreach ($extra['files'] as $fileGroup) {
                foreach ((array) $fileGroup as $file) {
                    if (isset($file['file']) && @is_file($file['file'])) {
                        $attachments[] = $file['file'];
                    }
                }
            }
        }

        $sent = wp_mail($toEmails, $subject, $body, $headers, $attachments);

        if ($pdfPath !== null) {
            $builder->cleanup($pdfPath);
        }

        // Build an informative log entry (shown against the action in the
        // submission record).
        $recipientList = implode(', ', $toEmails);

        if (!$sent) {
            return [
                'type'    => 'error',
                'message' => $pdfFailed
                    ? "PDF generation failed AND the email to {$recipientList} could not be sent."
                    : "PDF generated, but the email to {$recipientList} could not be sent.",
            ];
        }

        return [
            'type'    => 'success',
            'message' => $pdfFailed
                ? "Email sent to {$recipientList} WITHOUT the PDF (generation failed — see server logs)."
                : "PDF emailed to {$recipientList}.",
        ];
    }

    /* --------------------------------------------------------------------- */
    /* Rule evaluation                                                        */
    /* --------------------------------------------------------------------- */

    /**
     * Return the first matching rule, or null.
     */
    private function evaluateRules($rules, $form)
    {
        if (empty($rules) || !is_array($rules)) {
            return null;
        }
        foreach ($rules as $rule) {
            if ($this->checkCondition($rule, $form)) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * Evaluate a single rule's condition against the submitted form.
     */
    private function checkCondition($rule, $form)
    {
        $fieldId = $rule['field_id'] ?? '';
        $cond    = $rule['condition'] ?? 'equals';
        $target  = $rule['value'] ?? '';

        if ($fieldId === '') {
            return false;
        }

        $value = $this->getFieldValue($form, $fieldId);

        switch ($cond) {
            case 'equals':
                return strtolower($value) === strtolower($target);
            case 'not_equals':
                return strtolower($value) !== strtolower($target);
            case 'contains':
                return $target !== '' && stripos($value, $target) !== false;
            case 'not_contains':
                return stripos($value, $target) === false;
            case 'greater_than':
                return is_numeric($value) && is_numeric($target) && floatval($value) > floatval($target);
            case 'less_than':
                return is_numeric($value) && is_numeric($target) && floatval($value) < floatval($target);
            case 'is_set':
                return $value !== '';
            case 'is_empty':
                return $value === '';
            default:
                return false;
        }
    }

    /**
     * Read a field's submitted value from the $form field-object list.
     */
    private function getFieldValue($form, $fieldId)
    {
        if (!is_array($form)) {
            return '';
        }
        foreach ($form as $field) {
            $id = $field['advanced']['id'] ?? '';
            if ($id === $fieldId) {
                $value = $field['value'] ?? '';
                return is_array($value) ? implode(',', $value) : (string) $value;
            }
        }
        return '';
    }

    /* --------------------------------------------------------------------- */
    /* Template-tag rendering                                                 */
    /* --------------------------------------------------------------------- */

    /**
     * Replace {field_id} tokens (and {all_fields}) with submitted values.
     */
    public function renderData($form, $string, $allowHtml = false)
    {
        if (!is_string($string) || $string === '' || !is_array($form)) {
            return is_string($string) ? $string : '';
        }

        foreach ($form as $field) {
            $id    = $field['advanced']['id'] ?? '';
            $value = $field['value'] ?? '';
            if ($id === '') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $string = str_replace('{' . $id . '}', $value, $string);
        }

        if (strpos($string, '{all_fields}') !== false) {
            $string = str_replace('{all_fields}', $this->buildAllFieldsTable($form), $string);
        }

        return $string;
    }

    /**
     * Build an HTML table of all non-internal fields (for the {all_fields} tag).
     */
    private function buildAllFieldsTable($form)
    {
        if (empty($form)) {
            return '<p>No form data submitted.</p>';
        }

        $html = '<table style="border-collapse:collapse;width:100%;">';
        foreach ($form as $field) {
            $id    = $field['advanced']['id'] ?? '';
            $label = $field['label'] ?? '';
            $value = $field['value'] ?? '';
            $type  = $field['type'] ?? '';

            if ($id === '' || strpos($id, '_') === 0 || $type === 'html' || $type === 'hidden') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $display = $label !== '' ? $label : ucwords(str_replace(['_', '-'], ' ', $id));
            $html .= sprintf(
                '<tr><td style="padding:8px;border:1px solid #ddd;font-weight:bold;">%s</td>'
                . '<td style="padding:8px;border:1px solid #ddd;">%s</td></tr>',
                esc_html($display),
                esc_html($value)
            );
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Render tokens then strip to a safe single-line string (for headers etc.).
     */
    public function sanitize($form, $string)
    {
        $value = $this->renderData($form, (string) $string);
        $value = sanitize_text_field($value);
        $value = wp_kses_decode_entities(html_entity_decode($value, ENT_QUOTES));
        return sanitize_text_field($value);
    }

    /**
     * Split a comma-separated list into an array of valid email addresses.
     */
    private function getEmails($value)
    {
        if (!$value) {
            return [];
        }
        $emails = array_map('trim', explode(',', $value));
        $emails = array_filter($emails, 'is_email');
        return array_values($emails);
    }
}
