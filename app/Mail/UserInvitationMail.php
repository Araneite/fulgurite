<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Invitation $invitation,
        public string $acceptUrl,
        public ?string $inviterName = null,
        public array $roleLabels = [],
        public array $forcedActionLabels = [],
        public ?string $heroImageUrl = null,
        public ?string $logoUrl = null,
        string $mailLocale = 'en_US'        
    ) {
        $this->locale($mailLocale);
        $this->logoUrl ??= asset('assets/img/fulgurite-logo.svg');
    }
    
    public function envelope(): Envelope {
        return new Envelope(
            subject: trans('mails/user-invitation.subject', [
                'app' => config('app.name', 'Fulgurite'),
            ]),
        );
    }
    
    public function content(): Content {
        return new Content(
            view: 'mails.user-invitation',
            text: 'mails.user-invitation-text',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl'=> $this->acceptUrl,
                'inviterName'=> $this->inviterName,
                'roleLabels'=> array_values($this->roleLabels),
                'forcedActionLabels'=> array_values($this->forcedActionLabels),
                'heroImageUrl'=> $this->heroImageUrl,
                'logoUrl'=> $this->logoUrl,
            ]
        );
    }
    
    public function attachments(): array {
        return [];
    }
}
