<?php

namespace App\Http\Controllers;

use App\Models\ConversionJob;
use App\Services\AiServiceBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class ConverterController extends Controller
{
    protected AiServiceBridge $aiBridge;

    public function __construct(AiServiceBridge $aiBridge)
    {
        $this->aiBridge = $aiBridge;
    }

    public function index(Request $request)
    {
        $jobs = $request->user()->conversionJobs()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * PDF to Word (.docx)
     */
    public function pdfToWord(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
        ]);

        $uploaded = $request->file('file');
        $storedPath = $this->storeTempFile($uploaded);

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'pdf2word',
            'input_files' => [$storedPath],
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->convertPdfToWord($storedPath);
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chuyển đổi PDF sang Word thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Word/Office to PDF
     */
    public function wordToPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,doc,pptx,ppt,xlsx,xls|max:102400',
        ]);

        $uploaded = $request->file('file');
        $storedPath = $this->storeTempFile($uploaded);

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'word2pdf',
            'input_files' => [$storedPath],
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->convertWordToPdf($storedPath);
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chuyển đổi Word/Office sang PDF thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Merge Multiple PDFs
     */
    public function mergePdf(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:2',
            'files.*' => 'required|file|mimes:pdf|max:51200',
        ]);

        $storedPaths = [];
        foreach ($request->file('files') as $file) {
            $storedPaths[] = $this->storeTempFile($file);
        }

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'merge_pdf',
            'input_files' => $storedPaths,
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->mergePdfs($storedPaths);
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gộp các tệp PDF thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Split PDF
     */
    public function splitPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
            'page_ranges' => 'required|string', // e.g. "1-5,8"
        ]);

        $storedPath = $this->storeTempFile($request->file('file'));

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'split_pdf',
            'input_files' => [$storedPath],
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->splitPdf($storedPath, $request->page_ranges);
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tách trang PDF thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Convert Image format / compress
     */
    public function convertImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:png,jpg,jpeg,webp|max:20480',
            'format' => 'required|string|in:PNG,JPG,WEBP',
            'quality' => 'nullable|integer|min:10|max:100',
        ]);

        $storedPath = $this->storeTempFile($request->file('file'));

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'img_convert',
            'input_files' => [$storedPath],
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->convertImage(
                $storedPath,
                $request->format,
                $request->quality ?? 90
            );
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chuyển đổi định dạng ảnh thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Combine Images to PDF
     */
    public function imagesToPdf(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:png,jpg,jpeg,webp|max:20480',
        ]);

        $storedPaths = [];
        foreach ($request->file('files') as $file) {
            $storedPaths[] = $this->storeTempFile($file);
        }

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'img2pdf',
            'input_files' => $storedPaths,
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->imagesToPdf($storedPaths);
            $job->update([
                'status' => 'completed',
                'output_path' => $result['output_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gộp ảnh thành PDF thành công!',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lecture Video/Audio to Text Transcriber
     */
    public function transcribeLecture(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:mp4,mkv,avi,mov,mp3,wav,m4a|max:204800', // 200MB
        ]);

        $storedPath = $this->storeTempFile($request->file('file'));

        $job = ConversionJob::create([
            'user_id' => $request->user()->id,
            'tool_type' => 'transcribe',
            'input_files' => [$storedPath],
            'status' => 'processing',
        ]);

        try {
            $result = $this->aiBridge->transcribeLecture($storedPath, $request->user());
            $job->update([
                'status' => 'completed',
                'output_path' => $result['txt_path'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bóc băng bài giảng thành công kèm mốc thời gian!',
                'transcription' => $result['transcription'] ?? '',
                'download_url' => $result['download_url'] ?? null,
                'job' => $job,
            ]);
        } catch (Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function storeTempFile($file): string
    {
        $dest = storage_path('app/public/conversions_temp');
        if (!file_exists($dest)) {
            mkdir($dest, 0755, true);
        }
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($dest, $name);
        return $dest . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Get available TTS voices.
     */
    public function getTtsVoices()
    {
        $voices = [
            ["id" => "vi-VN-HoaiMyNeural", "name" => "Hoài My (Nữ VN - Chuẩn Song Ngữ Anh-Việt)", "lang" => "vi-VN", "badge" => "Song Ngữ ⭐"],
            ["id" => "vi-VN-NamMinhNeural", "name" => "Nam Minh (Nam VN - Trầm Ấm, Truyền Cảm)", "lang" => "vi-VN", "badge" => "Song Ngữ ⭐"],
            ["id" => "en-US-JennyNeural", "name" => "Jenny (Nữ US - Tự Nhiên, Trôi Chảy)", "lang" => "en-US", "badge" => "Tiếng Anh Mỹ"],
            ["id" => "en-US-GuyNeural", "name" => "Guy (Nam US - Chuẩn Giảng Viên)", "lang" => "en-US", "badge" => "Tiếng Anh Mỹ"],
            ["id" => "en-US-AriaNeural", "name" => "Aria (Nữ US - Thuyết Trình Tin Tức)", "lang" => "en-US", "badge" => "Tiếng Anh Mỹ"],
            ["id" => "en-GB-SoniaNeural", "name" => "Sonia (Nữ UK - Giọng Anh-Anh Quý Tộc)", "lang" => "en-GB", "badge" => "Tiếng Anh Anh"],
            ["id" => "ja-JP-NanamiNeural", "name" => "Nanami (Nữ Nhật Bản)", "lang" => "ja-JP", "badge" => "Tiếng Nhật"],
            ["id" => "ko-KR-SunHiNeural", "name" => "Sun-Hi (Nữ Hàn Quốc)", "lang" => "ko-KR", "badge" => "Tiếng Hàn"],
            ["id" => "zh-CN-XiaoxiaoNeural", "name" => "Xiaoxiao (Nữ Trung Quốc)", "lang" => "zh-CN", "badge" => "Tiếng Trung"],
            ["id" => "fr-FR-DeniseNeural", "name" => "Denise (Nữ Pháp)", "lang" => "fr-FR", "badge" => "Tiếng Pháp"],
        ];
        return response()->json(["success" => true, "voices" => $voices]);
    }

    /**
     * Synthesize Text-to-Speech using Python Edge-TTS Neural Engine.
     */
    public function synthesizeTts(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|min:1',
            'lang' => 'nullable|string',
            'voice' => 'nullable|string',
            'rate' => 'nullable|numeric|min:0.5|max:2.5',
        ]);

        try {
            $data = $this->aiBridge->synthesizeTts(
                text: $validated['text'],
                lang: $validated['lang'] ?? 'auto',
                voice: $validated['voice'] ?? null,
                rate: (float) ($validated['rate'] ?? 1.0)
            );

            // Construct full public playback URL
            $baseUrl = rtrim(config('app.url', 'http://localhost:8000'), '/');
            $fileName = $data['file_name'] ?? '';
            $playbackUrl = "{$baseUrl}/api/tts/audio/{$fileName}";

            return response()->json([
                'success' => true,
                'data' => array_merge($data, [
                    'playback_url' => $playbackUrl,
                ]),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi tổng hợp giọng đọc: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream or download generated TTS audio file.
     */
    public function getTtsAudio(Request $request, $fileName)
    {
        try {
            $response = $this->aiBridge->getTtsAudioStream($fileName);
            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'Tệp âm thanh không tồn tại.'], 404);
            }

            return response($response->body(), 200, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="' . basename($fileName) . '"',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Extract raw text from uploaded Word, PDF, TXT for TTS reader.
     */
    public function extractText(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt,docx,doc,pdf|max:20480',
        ]);

        $storedPath = $this->storeTempFile($request->file('file'));

        try {
            $text = $this->aiBridge->extractTextFromFile($storedPath);
            return response()->json([
                'success' => true,
                'text' => $text,
                'file_name' => $request->file('file')->getClientOriginalName(),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        } finally {
            if (file_exists($storedPath)) {
                @unlink($storedPath);
            }
        }
    }
}
