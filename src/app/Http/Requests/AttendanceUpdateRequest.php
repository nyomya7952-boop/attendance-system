<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['nullable', 'date_format:H:i'],
            'ended_at' => ['nullable', 'date_format:H:i'],
            'remarks' => ['required', 'string'],
            'breaks' => ['array'],
            'breaks.*.break_start_at' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'remarks.required' => '備考を記入してください',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attendanceId = $this->route('id');
            $attendance = $attendanceId ? Attendance::find($attendanceId) : null;
            if (!$attendance) {
                return;
            }

            $workDate = $attendance->work_date
                ? $attendance->work_date->toDateString()
                : now()->toDateString();

            $startedAtInput = $this->input('started_at');
            $endedAtInput = $this->input('ended_at');

            $startedAt = $this->parseTime($workDate, $startedAtInput);
            $endedAt = $this->parseTime($workDate, $endedAtInput);

            if ($startedAt && $endedAt && $startedAt->gt($endedAt)) {
                $validator->errors()->add('started_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($this->input('breaks', []) as $index => $break) {
                $breakStartInput = isset($break['break_start_at']) ? trim((string) $break['break_start_at']) : '';
                $breakEndInput = isset($break['break_end_at']) ? trim((string) $break['break_end_at']) : '';

                $breakStartAt = $this->parseTime($workDate, $breakStartInput);
                $breakEndAt = $this->parseTime($workDate, $breakEndInput);

                if ($breakStartAt && $startedAt && $breakStartAt->lt($startedAt)) {
                    $validator->errors()->add("breaks.$index.break_start_at", '休憩時間が不適切な値です');
                }
                if ($breakStartAt && $endedAt && $breakStartAt->gt($endedAt)) {
                    $validator->errors()->add("breaks.$index.break_start_at", '休憩時間が不適切な値です');
                }
                if ($breakEndAt && $endedAt && $breakEndAt->gt($endedAt)) {
                    $validator->errors()->add("breaks.$index.break_end_at", '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($breakStartAt && $breakEndAt && $breakEndAt->lt($breakStartAt)) {
                    $validator->errors()->add("breaks.$index.break_end_at", '休憩時間が不適切な値です');
                }
            }
        });
    }

    private function parseTime(string $workDate, ?string $time): ?Carbon
    {
        $time = $time !== null ? trim($time) : '';
        if ($time === '') {
            return null;
        }

        try {
            return Carbon::parse($workDate)->setTimeFromTimeString($time);
        } catch (\Exception $e) {
            return null;
        }
    }
}

