<?php

namespace CristalWindows\BreakdanceFormPdf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central definition of the form fields, their human-readable labels, and which
 * fields are included for each Request Reason.
 *
 * The label text and field ordering mirror exactly what was previously sent in
 * the Conditional Email body for each reason.
 */
class FieldConfig
{
    const REASON_GENERAL = 'General Information Request';
    const REASON_SERVICE = 'Service Call Request';
    const REASON_QUOTE   = 'Quotation Request';

    /**
     * Field id => human-readable label.
     */
    const LABELS = [
        'request_reason'    => 'Request Reason',
        'installation_date' => 'Approximate Date of Installation',
        'contract_number'   => 'Contract Number',
        'FIRSTSURNAME'      => 'Full Name',
        'email'             => 'Email',
        'alt_email'         => 'Alternative Email',
        'MOBTEL'            => 'Phone Number',
        'alt_phone'         => 'Alternative Phone',
        'HOUSENO'           => 'House Number',
        'STREET'            => 'Street',
        'TOWN'              => 'Town',
        'COUNTY'            => 'County',
        'PCODE'             => 'Postcode',
        'SUBSOURCE'         => 'Subject',
        'NOTES'             => 'Message',
    ];

    /**
     * Ordered list of field ids shared by General Information and Quotation
     * requests.
     */
    const BASE_ORDER = [
        'request_reason',
        'FIRSTSURNAME',
        'email',
        'alt_email',
        'MOBTEL',
        'alt_phone',
        'HOUSENO',
        'STREET',
        'TOWN',
        'COUNTY',
        'PCODE',
        'SUBSOURCE',
        'NOTES',
    ];

    /**
     * Ordered list of field ids for Service Call requests (adds the
     * installation date and contract number directly after the reason).
     */
    const SERVICE_ORDER = [
        'request_reason',
        'installation_date',
        'contract_number',
        'FIRSTSURNAME',
        'email',
        'alt_email',
        'MOBTEL',
        'alt_phone',
        'HOUSENO',
        'STREET',
        'TOWN',
        'COUNTY',
        'PCODE',
        'SUBSOURCE',
        'NOTES',
    ];

    /**
     * Return the ordered field ids to include for a given request reason.
     *
     * Unknown / empty reasons fall back to the base field set so a submission
     * is never dropped silently.
     *
     * @return string[]
     */
    public static function orderForReason(string $reason): array
    {
        if ($reason === self::REASON_SERVICE) {
            return self::SERVICE_ORDER;
        }

        // General Information and Quotation share the base field set.
        return self::BASE_ORDER;
    }

    /**
     * Human-readable label for a field id (falls back to the id itself).
     */
    public static function label(string $fieldId): string
    {
        return self::LABELS[$fieldId] ?? $fieldId;
    }
}
