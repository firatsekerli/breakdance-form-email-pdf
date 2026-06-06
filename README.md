# Cristal Breakdance Form PDF

A small, **standalone** WordPress plugin that adds a **"Send PDF"** action to
Breakdance forms.

When the contact form is submitted, this action:

1. Reads the submitted fields.
2. Builds a **branded PDF** (mPDF) containing **only the fields relevant to the
   selected _Request Reason_**.
3. Emails it to a fixed recipient with a **short summary in the body** and the
   **full details attached as a PDF**, plus **any uploaded files attached
   alongside** it.

It does **not** modify or replace the separate **"Conditional Email"** action
(which comes from a different plugin). Add "Send PDF" alongside your existing
_Actions After Submission_, and remove the duplicated field list from the
Conditional Email body in the Breakdance UI yourself.

---

## Request Reason → fields included

| Field | General Information | Service Call | Quotation |
|-------|:---:|:---:|:---:|
| Request Reason | ✓ | ✓ | ✓ |
| Approximate Date of Installation | – | ✓ | – |
| Contract Number | – | ✓ | – |
| Full Name | ✓ | ✓ | ✓ |
| Email | ✓ | ✓ | ✓ |
| Alternative Email | ✓ | ✓ | ✓ |
| Phone Number | ✓ | ✓ | ✓ |
| Alternative Phone | ✓ | ✓ | ✓ |
| House Number | ✓ | ✓ | ✓ |
| Street | ✓ | ✓ | ✓ |
| Town | ✓ | ✓ | ✓ |
| County | ✓ | ✓ | ✓ |
| Postcode | ✓ | ✓ | ✓ |
| Subject | ✓ | ✓ | ✓ |
| Message | ✓ | ✓ | ✓ |

Service Call and Quotation forms may include uploaded images — those files are
attached to the email alongside the PDF.

The field ids, labels and ordering are defined in
[`src/FieldConfig.php`](src/FieldConfig.php).

---

## Installation

```bash
composer install --no-dev
```

Then upload the whole plugin folder (including `vendor/`) to
`wp-content/plugins/` and activate it, **or** zip it and install via
_Plugins → Add New → Upload_.

> Requires the mPDF library, installed via Composer into `vendor/`. If it is
> missing, the plugin shows an admin notice and the action does nothing.

### Add the action to your form

In the Breakdance Form Builder, open **Actions After Submission** and add
**Send PDF** (alongside your existing actions). No further per-form
configuration is required.

---

## Configuration

### Recipient (required)

Set the fixed recipient address. In order of precedence:

1. Constant in `wp-config.php`:
   ```php
   define( 'CRISTAL_BD_PDF_RECIPIENT', 'enquiries@cristalwindows.co.uk' );
   ```
2. Filter:
   ```php
   add_filter( 'cristal_bd_pdf_recipient', fn() => 'enquiries@cristalwindows.co.uk' );
   ```
3. Fallback: the site admin email (`admin_email`).

### Other filters

| Filter | Purpose |
|--------|---------|
| `cristal_bd_pdf_recipient` | The "To" address. |
| `cristal_bd_pdf_subject` | Email subject (`$subject, $reason, $fields`). |
| `cristal_bd_pdf_summary_body` | HTML summary body (`$html, $reason, $fields`). |
| `cristal_bd_pdf_headers` | Email headers array (`$headers, $fields`). |
| `cristal_bd_pdf_email` | Full email context array before sending. |
| `cristal_bd_pdf_logo` | Logo URL or local path used in the PDF. |
| `cristal_bd_pdf_brand_colour` | Primary brand colour (hex). |
| `cristal_bd_pdf_strict_errors` | If `true`, surface PDF/email failures to Breakdance instead of logging silently. |

By default, an internal PDF/email failure is **logged** (via `error_log`) and
the visitor still sees a successful submission — so an internal hiccup never
blocks the form. Set `cristal_bd_pdf_strict_errors` to `true` to change that.

The Reply-To header is automatically set to the submitter's name and email so
replies go straight back to the customer.

---

## Branding

The PDF mirrors the look of the existing `quotation-form` plugin: Cristal blue
(`#1a5490`), Arial, the company logo, and a contact strip. The logo is loaded
from the live site URL by default; override it with the `cristal_bd_pdf_logo`
filter (a local file path also works and avoids a runtime fetch).

The template lives in [`templates/pdf.php`](templates/pdf.php).

---

## Development / testing

Render sample PDFs for all three reasons without a WordPress install:

```bash
composer install
php test/render-sample.php           # writes to a temp folder
php test/render-sample.php ./out     # or a folder of your choosing
```

The harness stubs the few WordPress functions the PDF code uses.
