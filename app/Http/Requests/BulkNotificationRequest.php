<?php

namespace App\Http\Requests;

use App\Services\Notification\Enums\RecipientChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkNotificationRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', Rule::in(RecipientChannel::values())],
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:5000',
            'priority' => 'sometimes|integer|min:0|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => 'Укажите канал отправки',
            'channel.in' => sprintf('Поддерживаются каналы: %s', implode(', ', RecipientChannel::values())),
            'recipients.required' => 'Укажите получателей',
            'recipients.min' => 'Укажите хотя бы одного получателя',
            'recipients.max' => 'Максимум 1000 получателей за раз',
            'message.required' => 'Текст сообщения обязателен',
            'priority.min' => 'Минимальный приоритет 0',
            'priority.max' => 'Максимальный приоритет 10',
        ];
    }

    public function channel(): string
    {
        return $this->post('channel');
    }

    /**
     * @return string[]
     */
    public function recipients(): array
    {
        return $this->post('recipients');
    }

    public function message(): string
    {
        return $this->post('message');
    }

    public function priority(): int
    {
        return $this->post('priority', 0);
    }
}
