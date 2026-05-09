<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactEnquiryMail extends Mailable
{
    public array $systemSettings = [];

    public function __construct(public Enquiry $enquiry)
    {
        $settingResource = app(SettingService::class)->getSettingByVariable('system');
        $this->systemSettings = $settingResource?->toArray(new Request())['value'] ?? [];
    }

    public function envelope(): Envelope
    {
        $subject = $this->enquiry->subject
            ? 'New Enquiry: ' . $this->enquiry->subject
            : 'New Contact Enquiry from ' . $this->enquiry->name;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.enquiry',
            with: ['systemSettings' => $this->systemSettings],
        );
    }
}
