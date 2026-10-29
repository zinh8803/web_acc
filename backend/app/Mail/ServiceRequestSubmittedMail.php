<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ServiceRequest $service;
    public string $customerName;
    public ?string $orderCode;
    public array $formData;
    public ?string $customerNote;
    public string $orderUrl;

    public function __construct(
        ServiceRequest $service,
        string $customerName,
        ?string $orderCode,
        array $formData,
        ?string $customerNote,
        string $orderUrl
    ) {
        $this->service = $service;
        $this->customerName = $customerName;
        $this->orderCode = $orderCode;
        $this->formData = $formData;
        $this->customerNote = $customerNote;
        $this->orderUrl = $orderUrl;
    }

    public function build()
    {
        $subject = "[Shop Acc] Tiếp nhận thông tin cày thuê - Yêu cầu #{$this->service->request_code}";

        return $this->subject($subject)
                    ->view('emails.service_request_submitted')
                    ->with([
                        'service' => $this->service,
                        'customerName' => $this->customerName,
                        'orderCode' => $this->orderCode,
                        'formData' => $this->formData,
                        'customerNote' => $this->customerNote,
                        'orderUrl' => $this->orderUrl,
                    ]);
    }
}
