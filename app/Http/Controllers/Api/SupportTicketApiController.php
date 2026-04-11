<?php

namespace App\Http\Controllers\Api;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketType;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketApiController extends Controller
{
    // ─── GET /api/user/support-tickets ───────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', Auth::id())
            ->with('ticketType')
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 15));

        $data = $tickets->map(fn ($t) => $this->formatTicket($t));

        return ApiResponseType::sendJsonResponse(true, 'Tickets retrieved.', [
            'data'         => $data->values(),
            'current_page' => $tickets->currentPage(),
            'last_page'    => $tickets->lastPage(),
            'total'        => $tickets->total(),
        ]);
    }

    // ─── POST /api/user/support-tickets ──────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_type_id' => 'required|exists:support_ticket_types,id',
            'subject'        => 'required|string|max:255',
            'description'    => 'required|string|max:5000',
        ]);

        $ticket = SupportTicket::create([
            'ticket_type_id' => $request->ticket_type_id,
            'user_id'        => Auth::id(),
            'subject'        => $request->subject,
            'email'          => Auth::user()->email,
            'description'    => $request->description,
            'status'         => SupportTicketStatusEnum::OPEN,
        ]);

        return ApiResponseType::sendJsonResponse(true, 'Support ticket created successfully.', $this->formatTicket($ticket));
    }

    // ─── GET /api/user/support-tickets/{id} ──────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $ticket = SupportTicket::where('user_id', Auth::id())
            ->with(['ticketType', 'messages'])
            ->findOrFail($id);

        return ApiResponseType::sendJsonResponse(true, 'Ticket retrieved.', [
            'ticket'   => $this->formatTicket($ticket),
            'messages' => $ticket->messages->map(fn ($m) => [
                'id'         => $m->id,
                'send_by'    => $m->send_by,
                'message'    => $m->message,
                'created_at' => $m->created_at->toIso8601String(),
            ])->values(),
        ]);
    }

    // ─── POST /api/user/support-tickets/{id}/reply ───────────────────────────

    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);

        $ticket = SupportTicket::where('user_id', Auth::id())->findOrFail($id);

        if (!$ticket->isOpen()) {
            return ApiResponseType::sendJsonResponse(false, 'This ticket is closed and cannot receive replies.');
        }

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'send_by'   => 'user',
            'message'   => $request->message,
        ]);

        // Re-open if resolved/pending review
        if (in_array($ticket->status, [SupportTicketStatusEnum::RESOLVED, SupportTicketStatusEnum::PENDING_REVIEW])) {
            $ticket->update(['status' => SupportTicketStatusEnum::REOPEN]);
        }

        return ApiResponseType::sendJsonResponse(true, 'Reply sent.', [
            'id'         => $message->id,
            'send_by'    => $message->send_by,
            'message'    => $message->message,
            'created_at' => $message->created_at->toIso8601String(),
        ]);
    }

    // ─── GET /api/support-ticket-types ───────────────────────────────────────

    public function types(): JsonResponse
    {
        $types = SupportTicketType::orderBy('title')->get(['id', 'title']);

        return ApiResponseType::sendJsonResponse(true, 'Ticket types retrieved.', $types);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function formatTicket(SupportTicket $ticket): array
    {
        $statusEnum = $ticket->status;

        return [
            'id'          => $ticket->id,
            'slug'        => $ticket->slug,
            'subject'     => $ticket->subject,
            'description' => $ticket->description,
            'status'      => $statusEnum instanceof SupportTicketStatusEnum ? $statusEnum->value : $ticket->status,
            'status_label'=> $statusEnum instanceof SupportTicketStatusEnum ? $statusEnum->label() : ucfirst($ticket->status),
            'type'        => $ticket->ticketType?->title,
            'created_at'  => $ticket->created_at->toIso8601String(),
            'updated_at'  => $ticket->updated_at->toIso8601String(),
        ];
    }
}
