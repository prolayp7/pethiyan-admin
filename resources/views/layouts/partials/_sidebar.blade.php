<aside class="navbar navbar-vertical navbar-expand-lg overflow-auto" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-brand navbar-brand-autodark">
            @php
                use App\Enums\AdminPermissionEnum;
                use App\Enums\SellerPermissionEnum;
                use App\Enums\DefaultSystemRolesEnum;
                use Illuminate\Support\Facades\Auth;
                $panel = request()->segment(1);
            @endphp
            <a href="{{ url($panel) }}">
                <img src="{{ !empty($systemSettings['logo']) ? $systemSettings['logo'] : asset('logos/hyper-local-logo-white.png') }}"
                     alt="{{$systemSettings['appName'] ?? ""}}" width="150">
            </a>
        </div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php
                    // Get the menu items based on the authenticated user's role.
                    $userRole = Auth::user();
                    $currentPanel = $userRole->access_panel->value ?? $panel;
                    $menuItems = config("menu.{$currentPanel}", []);

                    // Choose the correct permission set per panel
                    $allPerms = $currentPanel === 'admin'
                        ? AdminPermissionEnum::values()
                        : (($currentPanel === 'seller') ? SellerPermissionEnum::values() : []);

                    // Super Admin bypass only applies to admin panel
                    $isSuperAdmin = $currentPanel === 'admin' && $userRole->hasRole(DefaultSystemRolesEnum::SUPER_ADMIN());

                    // Default Seller role can view the full seller module
                    $isDefaultSeller = $currentPanel === 'seller' && $userRole->hasRole(DefaultSystemRolesEnum::SELLER());

                    $canSee = function ($perm) use ($userRole, $allPerms, $isSuperAdmin, $isDefaultSeller) {
                        // If default seller role on seller panel -> always visible
                        if ($isDefaultSeller) {
                            return true;
                        }
                        // No permission specified -> visible
                        if (empty($perm)) {
                            return true;
                        }
                        // If permission slug does not exist in the panel's enum -> default visible
                        if (!in_array($perm, $allPerms, true)) {
                            return true;
                        }
                        // Super Admin bypass
                        if ($isSuperAdmin) {
                            return true;
                        }
                        try {
                            return $userRole->hasPermissionTo($perm);
                        } catch (\Throwable $e) {
                            return false;
                        }
                    };
                @endphp

                @foreach ($menuItems as $key => $item)
                    @php
                        // Determine if the menu item has sub-routes (dropdown) or a single link.
                        $isDropdown = is_array($item['route']);
                        $isActive = isset($page, $item['active']) && $page === $item['active'];
                        // Check permission for single link or any children for dropdown
                        if (!$isDropdown) {
                            if (!$canSee($item['permission'] ?? null)) {
                                continue; // skip this menu item if not permitted
                            }
                        }
                        // For dropdowns, pre-filter sub items; if none remain, skip parent entirely
                        $visibleSubRoutes = null;
                        if ($isDropdown) {
                            $visibleSubRoutes = [];
                            foreach ($item['route'] as $srKey => $subRoute) {
                                $perm = is_array($subRoute) ? ($subRoute['permission'] ?? null) : null;
                                if ($canSee($perm)) {
                                    $visibleSubRoutes[$srKey] = $subRoute;
                                }
                            }
                            if (count($visibleSubRoutes) === 0) {
                                continue; // no visible children => hide the whole dropdown
                            }
                        }
                    @endphp

                    <li class="nav-item {{ $isActive ? 'active' : '' }}">
                        @if ($isDropdown)
                            {{-- Collapse-based sub-menu: expands inline so it is never clipped by the sidebar's overflow-auto --}}
                            <a class="nav-link {{ $isActive ? 'active' : '' }}"
                               href="#navbar-{{ $key }}"
                               data-bs-toggle="collapse"
                               role="button"
                               aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                               aria-controls="navbar-{{ $key }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti {{ $item['icon'] }} fs-2"></i>
                                </span>
                                <span class="nav-link-title">{{ __($item['title']) }}</span>
                            </a>
                            @if (count($visibleSubRoutes) > 0)
                                <div class="navbar-submenu collapse {{ $isActive ? 'show' : '' }}" id="navbar-{{ $key }}">
                                    <ul class="navbar-nav">
                                        @foreach ($visibleSubRoutes as $subRoute)
                                            @php
                                                $isSubActive = isset($sub_page) && $subRoute['sub_active'] === $sub_page;
                                            @endphp
                                            <li class="nav-item {{ $isSubActive ? 'active' : '' }}">
                                                <a class="nav-link {{ $isSubActive ? 'active' : '' }}"
                                                   href="{{ route($subRoute['sub_route'], $subRoute['route_param'] ?? []) }}">
                                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                                        <i class="ti ti-point{{ $isSubActive ? '-filled' : '' }} fs-2"></i>
                                                    </span>
                                                    <span class="nav-link-title">{{ __($subRoute['sub_title']) }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @else
                            <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <span class="nav-link-icon">
                                    <i class="ti {{ $item['icon'] }} fs-2"></i>
                                </span>
                                <span class="nav-link-title">{{ __($item['title']) }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</aside>
