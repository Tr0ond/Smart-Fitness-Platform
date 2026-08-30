<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PtChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $messageId,
        private readonly int $sequence,
        private readonly int $senderId,
        private readonly string $senderType,
        private readonly string $content,
        private readonly string $sentAt,
    ) {}

    /**
     * Phát tin nhắn vào đúng private channel của hội thoại sau khi service đã commit.
     * Channel authorization xác minh người đăng ký là participant lịch sử của hội thoại.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('pt.conversation.'.$this->conversationId)];
    }

    /** Tên sự kiện ổn định để Web/Mobile phân biệt với các transport event khác. */
    public function broadcastAs(): string
    {
        return 'pt.chat.message.sent';
    }

    /**
     * Chỉ phát các trường client cần để đồng bộ tin nhắn; không serialize raw model,
     * thông tin Membership, token, email hoặc dữ liệu nội bộ của outbox.
     *
     * @return array<string, int|string>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'sequence' => $this->sequence,
            'sender_id' => $this->senderId,
            'sender_type' => $this->senderType,
            'content' => $this->content,
            'sent_at' => $this->sentAt,
        ];
    }
}
