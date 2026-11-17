<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP - NoteForge AI</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0B2240;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1F2937;
        }
        .container {
            max-width: 580px;
            margin: 40px auto;
            background: #F7F4EC;
            border: 2px solid #0D2E5C;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .header {
            background-color: #0D2E5C;
            color: #FFFFFF;
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #FFB627;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            color: #FFB627;
        }
        .content {
            padding: 35px 30px;
            line-height: 1.6;
        }
        .otp-box {
            background: #0D2E5C;
            border: 2px dashed #FFB627;
            border-radius: 8px;
            text-align: center;
            padding: 20px;
            margin: 25px 0;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 38px;
            font-weight: 800;
            letter-spacing: 10px;
            color: #FFB627;
        }
        .footer {
            background: #EDE8D0;
            text-align: center;
            padding: 15px;
            font-size: 13px;
            color: #6B7280;
            border-top: 1px solid #D5CEB5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>NOTETRACK &bull; NOTETEXT AI</h1>
            <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;">RAG Study & Document Conversion Platform</p>
        </div>
        <div class="content">
            <p>Xin chào <strong>{{ $name }}</strong>,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản trên nền tảng <strong>NoteForge AI</strong>. Để hoàn tất kích hoạt tài khoản, vui lòng sử dụng mã xác thực OTP 6 số dưới đây:</p>
            
            <div class="otp-box">
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <p style="color: #DC2626; font-size: 14px;"><strong>Lưu ý:</strong> Mã này chỉ có hiệu lực trong vòng <strong>5 phút</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai khác.</p>
            <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} NoteForge AI Platform. Thiết kế phục vụ học tập và nghiên cứu.
        </div>
    </div>
</body>
</html>
