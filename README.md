# Cristal Breakdance Form PDF

A proper WordPress plugin that adds a standalone **"Send PDF"** action to
Breakdance forms — fully configured in the Breakdance UI, no hardcoded fields.

On submission it:

1. Evaluates a set of **conditional rules** (each rule = a condition on a form
   field) to choose **the recipient** and **the rich-text content** for the
   document.
2. Renders that content into a **branded PDF** (mPDF).
3. Emails it with a **short summary in the body**, the **PDF attached**, and
   **uploaded files attached alongside** (optional).

It does **not** modify or replace the separate **"Conditional Email"** action
(which comes from a different plugin). Add "Send PDF" alongside your existing
_Actions After Submission_.

---

## How it works (UI-driven)

The action is built on the Breakdance Forms Action API — the same pattern used
by the Phox "Conditional Email" plugin:

- Registered on `breakdance_loaded` (with version fallbacks).
- `controls()` builds the Form Builder UI.
- Field pickers and the `{ }` variable buttons populate from the form's own
  fields (`content.form.fields`), so nothing is hardcoded.
- `run($form, $settings, $extra)` resolves `{field_id}` tokens, renders the
  PDF, and sends the email.

### The controls

**PDF Content & Recipient Rules**
- **Rules** (repeater) — each rule has:
  - **Field** / **Condition** / **Value** — when this rule matches
    (e.g. `request_reason` *Equals* `Service Call Request`).
  - **Send To** — recipient for this rule.
  - **PDF Content** — rich text with the `{field}` variable picker (and an
    `{all_fields}` token). This is what gets rendered into the PDF.
  - First matching rule wins.
- **Default Recipient** — used when no rule matches.
- **Default PDF Content** — used when no rule matches.
- **PDF Title** — heading at the top of the PDF (supports field variables).

**Email Settings**
- **Subject**, **From Email**, **From Name**, **Reply To**, **CC**, **BCC**
  (all support field variables where relevant).
- **Email Body (summary)** — short rich-text summary shown in the email body
  (full detail lives in the attached PDF).
- **Attach uploaded files** — also attach any files the visitor uploaded.

### Replicating the three Request Reasons

Add one rule per reason, each with `request_reason` *Equals* the reason and the
matching PDF content. For example, for **Service Call Request** paste:

```html
<div style="font-size:18px;"><strong>Request Reason:</strong> {request_reason}</div>
<div style="font-size:18px;"><strong>Approximate Date of Installation:</strong> {installation_date}</div>
<div style="font-size:18px;"><strong>Contract Number:</strong> {contract_number}</div>
<div style="font-size:18px;"><strong>Full Name:</strong> {FIRSTSURNAME}</div>
<div style="font-size:18px;"><strong>Email:</strong> {email}</div>
... etc ...
```

(Use the variable picker to insert the tokens instead of typing them.) Set the
**Default PDF Content** to your General Information layout so unmatched
submissions still produce a sensible PDF.

---

## Installation

```bash
composer install --no-dev
```

Upload the whole plugin folder (including `vendor/`) to `wp-content/plugins/`
and activate it, **or** zip it and install via _Plugins → Add New → Upload_.

> Requires mPDF (installed via Composer into `vendor/`). If it's missing the
> plugin shows an admin notice and the action does nothing. If your server
> already provides mPDF, this bundled copy does not conflict (Composer
> autoloading is lazy and guarded by `class_exists`).

Then, in the Form Builder, open **Actions After Submission** → add **Send PDF**,
and configure the rules and email settings.

---

## Branding

The PDF shell mirrors the existing `quotation-form` plugin: Cristal blue
(`#1a5490`), Arial, the company logo, and a contact strip. The logo loads from
the live site URL by default; override it with the `cristal_bd_pdf_logo` filter
(a local file path also works). Override the colour with
`cristal_bd_pdf_brand_colour`.

The template lives in [`templates/pdf.php`](templates/pdf.php).

---

## Development / testing

Render sample PDFs without a WordPress install:

```bash
composer install
php test/render-sample.php           # writes to a temp folder
php test/render-sample.php ./out     # or a folder of your choosing
```

The harness stubs the few WordPress functions the PDF code uses and feeds in
sample rendered content.

---

## File structure

```
cristal-breakdance-pdf.php   Plugin bootstrap + registration (breakdance_loaded)
src/Actions/SendPdf.php      The Send PDF action: controls() + run() + rule logic
src/PdfBuilder.php           mPDF rendering + branded shell
templates/pdf.php            Branded PDF HTML/CSS
test/render-sample.php       Standalone PDF render harness
vendor/                      Composer dependencies (mPDF) — committed, upload-ready
```
