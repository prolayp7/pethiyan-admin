<?php

namespace App\Http\Controllers;

use App\Enums\AdminPermissionEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketType;
use App\Traits\ChecksPermissions;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    use ChecksPermissions, PanelAware;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeSupportTicketPermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    // ─── Index (datatable view) ───────────────────────────────────────────────

    public function index(): View
    {
        $columns = [
            ['data' => 'id',         'name' => 'id',         'title' => __('labels.id')],
            ['data' => 'subject',    'name' => 'subject',    'title' => __('labels.subject')],
            ['data' => 'user',       'name' => 'user',       'title' => __('labels.customer'),  'orderable' => false, 'searchable' => false],
            ['data' => 'type',       'name' => 'type',       'title' => __('labels.type'),       'orderable' => false, 'searchable' => false],
            ['data' => 'status',     'name' => 'status',     'title' => __('labels.status'),     'orderable' => false, 'searchable' => false],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('labels.created_at')],
            ['data' => 'action',     'name' => 'action',     'title' => __('labels.action'),     'orderable' => false, 'searchable' => false],
        ];

        $statuses   = SupportTicketStatusEnum::cases();
        $ticketTypes = SupportTicketType::orderBy('title')->get();

        return view('admin.support_tickets.index', compact('columns', 'statuses', 'ticketTypes'));
    }

    // ─── Datatable AJAX ──────────────────────────────────────────────────────

    public function datatable(Request $request): JsonResponse
    {
        try {
            $draw        = $request->get('draw');
            $start       = (int) $request->get('start', 0);
            $length      = (int) $request->get('length', 10);
            $search      = $request->get('search')['value'] ?? '';
            $status      = $request->get('status');
            $typeId      = $request->get('ticket_type_id');

            $orderColIdx = $request->get('order')[0]['column'] ?? 0;
            $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';
            $cols        = ['id', 'subject', 'user', 'type', 'status', 'created_at'];
            $orderCol    = in_array($cols[$orderColIdx] ?? '', ['id', 'subject', 'created_at'])
                ? ($cols[$orderColIdx] ?? 'id') : 'id';

            $query = SupportTicket::with(['user', 'ticketType']);

            $totalRecords = $query->count();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                      ->orWhere('email',   'like', "%{$search}%")
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            }

            if (!empty($status)) {
                $query->where('status', $status);
            }

            if (!empty($typeId)) {
                $query->where('ticket_type_id', $typeId);
            }

            $filteredRecords = $query->count();

            $tickets = $query->orderBy($orderCol, $orderDir)->skip($start)->take($length)->get();

            $data = $tickets->map(function (SupportTicket $ticket) {
                $statusEnum  = $ticket->status;
                $statusLabel = $statusEnum instanceof SupportTicketStatusEnum
                    ? $statusEnum->label()
                    : ucfirst(str_replace('_', ' ', $ticket->status));

                $badgeColor = match($statusEnum) {
                    SupportTicketStatusEnum::OPEN           => 'primary',
                    SupportTicketStatusEnum::IN_PROGRESS    => 'warning',
                    SupportTicketStatusEnum::REOPEN         => 'orange',
                    SupportTicketStatusEnum::PENDING_REVIEW => 'purple',
                    SupportTicketStatusEnum::RESOLVED       => 'success',
                    SupportTicketStatusEnum::CLOSED         => 'secondary',
                    default                                 => 'secondary',
                };

                return [
                    'id'         => $ticket->id,
                    'subject'    => Str::limit($ticket->subject, 50),
                    'user'       => $ticket->user?->name ?? $ticket->email,
                    'type'       => $ticket->ticketType?->title ?? '—',
                    'status'     => "<span class=\"badge bg-{$badgeColor}-lt\">{$statusLabel}</span>",
                    'created_at' => $ticket->created_at->format('d M Y'),
                    'action'     => $this->renderActions($ticket),
                ];
            })->toArray();

            return response()->json([
                'draw'            => intval($draw),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $data,
            ]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to load tickets: ' . $e->getMessage(), []);
        }
    }

    // ─── Show (detail + reply) ────────────────────────────────────────────────

    public function show(int $id): View
    {
        $ticket    = SupportTicket::with(['user', 'ticketType', 'messages.user'])->findOrFail($id);
        $statuses  = SupportTicketStatusEnum::cases();

        return view('admin.support_tickets.show', compact('ticket', 'statuses'));
    }

    // ─── Update status ────────────────────────────────────────────────────────

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['status' => 'required|in:' . implode(',', SupportTicketStatusEnum::values())]);
            $ticket = SupportTicket::findOrFail($id);
            $ticket->update(['status' => $request->status]);

            return ApiResponseType::sendJsonResponse(true, 'Status updated successfully.', ['status' => $ticket->status]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to update status: ' . $e->getMessage(), []);
        }
    }

    // ─── Admin reply ──────────────────────────────────────────────────────────

    public function reply(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['message' => 'required|string|max:5000']);
            $ticket = SupportTicket::findOrFail($id);

            $message = SupportTicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id'   => Auth::id(),
                'send_by'   => 'admin',
                'message'   => $request->message,
            ]);

            // Move ticket back to in_progress when admin replies
            if ($ticket->status === SupportTicketStatusEnum::OPEN || $ticket->status === SupportTicketStatusEnum::REOPEN) {
                $ticket->update(['status' => SupportTicketStatusEnum::IN_PROGRESS]);
            }

            return ApiResponseType::sendJsonResponse(true, 'Reply sent.', [
                'id'         => $message->id,
                'message'    => $message->message,
                'send_by'    => $message->send_by,
                'created_at' => $message->created_at->format('d M Y, H:i'),
                'author'     => Auth::user()->name,
            ]);
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to send reply: ' . $e->getMessage(), []);
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $ticket = SupportTicket::findOrFail($id);
            $ticket->delete();

            return ApiResponseType::sendJsonResponse(true, 'Ticket deleted successfully.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, 'Failed to delete ticket: ' . $e->getMessage(), []);
        }
    }

    // ─── Ticket Types CRUD ────────────────────────────────────────────────────

    public function storeType(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:100|unique:support_ticket_types,title']);
        $type = SupportTicketType::create(['title' => $request->title]);

        return ApiResponseType::sendJsonResponse(true, 'Ticket type created.', $type);
    }

    public function updateType(Request $request, int $id): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:100|unique:support_ticket_types,title,' . $id]);
        $type = SupportTicketType::findOrFail($id);
        $type->update(['title' => $request->title]);

        return ApiResponseType::sendJsonResponse(true, 'Ticket type updated.', $type);
    }

    public function destroyType(int $id): JsonResponse
    {
        try {
            $type = SupportTicketType::findOrFail($id);
            if ($type->tickets()->exists()) {
                return ApiResponseType::sendJsonResponse(false, 'Cannot delete type with existing tickets.');
            }
            $type->delete();

            return ApiResponseType::sendJsonResponse(true, 'Ticket type deleted.');
        } catch (\Throwable $e) {
            return ApiResponseType::sendJsonResponse(false, $e->getMessage(), []);
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function renderActions(SupportTicket $ticket): string
    {
        $viewUrl   = route('admin.support-tickets.show', $ticket->id);
        $deleteUrl = route('admin.support-tickets.destroy', $ticket->id);

        return <<<HTML
            <div class="d-flex gap-2">
                <a href="{$viewUrl}" class="btn btn-sm btn-outline-primary" title="View">
                    <i class="ti ti-eye fs-5"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-url="{$deleteUrl}" title="Delete">
                    <i class="ti ti-trash fs-5"></i>
                </button>
            </div>
        HTML;
    }

    private function authorizeSupportTicketPermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index', 'datatable', 'show' => AdminPermissionEnum::SUPPORT_TICKET_VIEW->value,
            'updateStatus', 'reply', 'storeType', 'updateType', 'destroyType' => AdminPermissionEnum::SUPPORT_TICKET_EDIT->value,
            'destroy' => AdminPermissionEnum::SUPPORT_TICKET_DELETE->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return $this->unauthorizedResponse();
        }

        abort(403, 'Unauthorized action.');
    }
}
