<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Traits\ChecksPermissions;
use Illuminate\Http\Request;
use App\Models\Enquiry;

class EnquiryController extends Controller
{
    use ChecksPermissions;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            if ($response = $this->authorizeEnquiryPermission($request)) {
                return $response;
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $enquiries = Enquiry::latest()->paginate(20);
        return view('admin.enquiries.index', compact('enquiries'));
    }

    public function show(Enquiry $enquiry)
    {
        if ($enquiry->status === 'unread') {
            $enquiry->update(['status' => 'read']);
        }
        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted successfully.');
    }

    private function authorizeEnquiryPermission(Request $request)
    {
        $permission = match ($request->route()?->getActionMethod()) {
            'index', 'show' => AdminPermissionEnum::ENQUIRY_VIEW->value,
            'destroy' => AdminPermissionEnum::ENQUIRY_DELETE->value,
            default => null,
        };

        if ($permission === null || $this->hasPermission($permission)) {
            return null;
        }

        abort(403, 'Unauthorized action.');
    }
}
