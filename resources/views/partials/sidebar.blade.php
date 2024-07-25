<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item">
            <a href="{{ url('/') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>
        <!-- Layouts -->
        <li class="menu-item active open">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div>Git Tracker</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ \App\helpers\isActiveRoute('repository.repository-list-view') }}">
                    <a class="menu-link" href="{{ url(route('repository.repository-list-view')) }}">
                        <div>Repository</div>
                    </a>
                </li>
                <li class="menu-item {{ \App\helpers\isActiveRoute('token.index') }}">
                    <a class="menu-link" href="{{ url(route('token.index')) }}">
                        <div>Token</div>
                    </a>
                </li>
                <li class="menu-item {{ \App\helpers\isActiveRoute('repository.auto-create-view') }}">
                    <a class="menu-link" href="{{ url(route('repository.auto-create-view')) }}">
                        <div>Repository Auto Creation</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
