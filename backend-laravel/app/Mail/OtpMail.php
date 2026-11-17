<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public string $name;

    public function __construct(string $otpCode, string $name = 'Sinh viên')
    {
        $this->otpCode = $otpCode;
        $this->name = $name;
    }

    public function build()
    {
        return $this->subject('Mã xác thực đăng ký tài khoản NoteForge AI')
                    ->view('emails.otp');
    }
}
