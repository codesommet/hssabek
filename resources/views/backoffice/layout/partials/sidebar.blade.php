    <!-- Sidenav Menu Start -->
    <div class="two-col-sidebar" id="two-col-sidebar">
        <div class="twocol-mini">
            <!-- Add -->
            <div class="dropdown">
                <a class="btn btn-primary bg-gradient btn-sm btn-icon rounded-circle d-flex align-items-center justify-content-center"
                    data-bs-toggle="dropdown" href="javascript:void(0);" role="button" data-bs-display="static"
                    data-bs-reference="parent">
                    <i class="isax isax-add"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-start">
                    <li>
                        <a href="{{ url('add-invoice') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-document-text-1 me-2"></i>Invoice
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('expenses') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-money-send me-2"></i>Expense
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('add-credit-notes', 'add-credit-notes', 'edit-credit-notes') }}"
                            class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-money-add me-2"></i>Credit Notes
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('add-debit-notes') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-money-recive me-2"></i>Debit Notes
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('add-purchases-orders') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-document me-2"></i>Purchase Order
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('add-quotation') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-document-download me-2"></i>Quotation
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('add-delivery-challan') }}" class="dropdown-item d-flex align-items-center">
                            <i class="isax isax-document-forward me-2"></i>Delivery Challan
                        </a>
                    </li>
                </ul>
            </div>
            <!-- /Add -->

            <ul class="menu-list">
                <li>
                    <a href="{{ url('account-settings') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                        data-bs-title="Settings"><i class="isax isax-setting-25"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                        data-bs-title="Documentation"><i class="isax isax-document-normal4"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="right"
                        data-bs-title="Changelog"><i class="isax isax-cloud-change5"></i></a>
                </li>
                <li>
                    <a href="{{ url('login') }}"><i class="isax isax-login-15"></i></a>
                </li>
            </ul>
        </div>
        <div class="sidebar" id="sidebar-two">

            <!-- Start Logo -->
            <div class="sidebar-logo">
                <a href="{{ url('index') }}" class="logo logo-normal">
                    <img src="{{ URL::asset('build/img/logo.svg') }}" alt="Logo">
                </a>
                <a href="{{ url('index') }}" class="logo-small">
                    <img src="{{ URL::asset('build/img/logo-small.svg') }}" alt="Logo">
                </a>
                <a href="{{ url('index') }}" class="dark-logo">
                    <img src="{{ URL::asset('build/img/logo-white.svg') }}" alt="Logo">
                </a>
                <a href="{{ url('index') }}" class="dark-small">
                    <img src="{{ URL::asset('build/img/logo-small-white.svg') }}" alt="Logo">
                </a>

                <!-- Sidebar Hover Menu Toggle Button -->
                <a id="toggle_btn" href="javascript:void(0);">
                    <i class="isax isax-menu-1"></i>
                </a>
            </div>
            <!-- End Logo -->

            <!-- Search -->
            <div class="sidebar-search">
                <div class="input-icon-end position-relative">
                    <input type="text" class="form-control" placeholder="Search">
                    <span class="input-icon-addon">
                        <i class="isax isax-search-normal"></i>
                    </span>
                </div>
            </div>
            <!-- /Search -->

            <!--- Sidenav Menu -->
            <div class="sidebar-inner" data-simplebar>
                <div id="sidebar-menu" class="sidebar-menu">

                    {{-- ============================================================ --}}
                    {{-- 👑 SUPER ADMIN SIDEBAR (user with tenant_id === null)         --}}
                    {{-- ============================================================ --}}
                    @if (auth()->check() && auth()->user()->tenant_id === null)
                        <ul>
                            <li class="menu-title"><span>Super Admin</span></li>
                            <li>
                                <ul>
                                    <li class="{{ request()->routeIs('sa.dashboard') ? 'active' : '' }}">
                                        <a href="{{ route('sa.dashboard') }}">
                                            <i class="isax isax-home-25"></i><span>Tableau de bord</span>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('sa.tenants.*') ? 'active' : '' }}">
                                        <a href="{{ route('sa.tenants.index') }}">
                                            <i class="isax isax-buildings-25"></i><span>Tenants</span>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('sa.plans.*') ? 'active' : '' }}">
                                        <a href="{{ route('sa.plans.index') }}">
                                            <i class="isax isax-layer5"></i><span>Plans</span>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('sa.subscriptions.*') ? 'active' : '' }}">
                                        <a href="{{ route('sa.subscriptions.index') }}">
                                            <i class="isax isax-receipt-text5"></i><span>Abonnements</span>
                                        </a>
                                    </li>
                                    <li class="{{ request()->routeIs('sa.access.*') ? 'active' : '' }}">
                                        <a href="{{ route('sa.access.roles.index') }}">
                                            <i class="isax isax-shield-tick"></i><span>Rôles & Permissions</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- 🏢 TENANT BACKOFFICE SIDEBAR (regular tenant users)           --}}
                    {{-- ============================================================ --}}
                    @if (auth()->check() && auth()->user()->tenant_id !== null)
                        <ul>
                            <li class="menu-title"><span>Main</span></li>
                            <li>
                                <ul>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('index', '/', 'admin-dashboard') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-element-45"></i><span>Dashboard</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('index') }}"
                                                    class="{{ Request::is('index', '/') ? 'active' : '' }}">Admin
                                                    Dashboard</a></li>
                                            <li><a href="{{ url('admin-dashboard') }}"
                                                    class="{{ Request::is('admin-dashboard') ? 'active' : '' }}">Admin
                                                    Dashboard 2</a></li>
                                        </ul>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('chat', 'voice-call', 'video-call', 'outgoing-call', 'incoming-call', 'call-history', 'calendar', 'email', 'email-reply', 'todo', 'notes', 'social-feed', 'file-manager', 'kanban-view', 'contacts', 'invoice', 'search-list') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-category-25"></i><span>Applications</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('chat') }}"
                                                    class="{{ Request::is('chat') ? 'active' : '' }}">Chat</a></li>
                                            <li class="submenu submenu-two">
                                                <a href="{{ url('javascript:void(0);') }}"
                                                    class="{{ Request::is('voice-call', 'video-call', 'outgoing-call', 'incoming-call', 'call-history') ? 'active subdrop' : '' }}">Calls<span
                                                        class="menu-arrow inside-submenu"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('voice-call') }}"
                                                            class="{{ Request::is('voice-call') ? 'active' : '' }}">Voice
                                                            Call</a></li>
                                                    <li><a href="{{ url('video-call') }}"
                                                            class="{{ Request::is('video-call') ? 'active' : '' }}">Video
                                                            Call</a></li>
                                                    <li><a href="{{ url('outgoing-call') }}"
                                                            class="{{ Request::is('outgoing-call') ? 'active' : '' }}">Outgoing
                                                            Call</a></li>
                                                    <li><a href="{{ url('incoming-call') }}"
                                                            class="{{ Request::is('incoming-call') ? 'active' : '' }}">Incoming
                                                            Call</a></li>
                                                    <li><a href="{{ url('call-history') }}"
                                                            class="{{ Request::is('call-history') ? 'active' : '' }}">Call
                                                            History</a></li>
                                                </ul>
                                            </li>
                                            <li><a href="{{ url('calendar') }}"
                                                    class="{{ Request::is('calendar') ? 'active' : '' }}">Calendar</a>
                                            </li>
                                            <li><a href="{{ url('email') }}"
                                                    class="{{ Request::is('email', 'email-reply') ? 'active' : '' }}">Email</a>
                                            </li>
                                            <li><a href="{{ url('todo') }}"
                                                    class="{{ Request::is('todo') ? 'active' : '' }}">To Do</a></li>
                                            <li><a href="{{ url('notes') }}"
                                                    class="{{ Request::is('notes') ? 'active' : '' }}">Notes</a></li>
                                            <li><a href="{{ url('social-feed') }}"
                                                    class="{{ Request::is('social-feed') ? 'active' : '' }}">Social
                                                    Feed</a></li>
                                            <li><a href="{{ url('file-manager') }}"
                                                    class="{{ Request::is('file-manager') ? 'active' : '' }}">File
                                                    Manager</a></li>
                                            <li><a href="{{ url('kanban-view') }}"
                                                    class="{{ Request::is('kanban-view') ? 'active' : '' }}">Kanban</a>
                                            </li>
                                            <li><a href="{{ url('contacts') }}"
                                                    class="{{ Request::is('contacts') ? 'active' : '' }}">Contacts</a>
                                            </li>
                                            <li><a href="{{ url('invoice') }}"
                                                    class="{{ Request::is('invoice') ? 'active' : '' }}">Invoices</a>
                                            </li>
                                            <li><a href="{{ url('search-list') }}"
                                                    class="{{ Request::is('search-list') ? 'active' : '' }}">Search
                                                    List</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-title"><span>Inventory & Sales</span></li>
                            <li>
                                <ul>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('products', 'add-product', 'edit-product', 'category', 'units') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-box5"></i><span>Product / Services</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('products') }}"
                                                    class="{{ Request::is('products', 'add-product', 'edit-product') ? 'active' : '' }}">Products</a>
                                            </li>
                                            <li><a href="{{ url('category') }}"
                                                    class="{{ Request::is('category') ? 'active' : '' }}">Category</a>
                                            </li>
                                            <li><a href="{{ url('units') }}"
                                                    class="{{ Request::is('units') ? 'active' : '' }}">Units</a></li>
                                        </ul>
                                    </li>
                                    <li class="{{ Request::is('inventory') ? 'active' : '' }}">
                                        <a href="{{ url('inventory') }}">
                                            <i class="isax isax-lifebuoy5"></i><span>Inventory</span>
                                        </a>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('invoices', 'edit-invoice', 'add-invoice', 'invoice-details', 'invoice-templates', 'recurring-invoices') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-receipt-item5"></i><span>Invoices</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('invoices') }}"
                                                    class="{{ Request::is('invoices', 'edit-invoice') ? 'active' : '' }}">Invoices</a>
                                            </li>
                                            <li><a href="{{ url('add-invoice') }}"
                                                    class="{{ Request::is('add-invoice') ? 'active' : '' }}">Create
                                                    Invoice</a></li>
                                            <li><a href="{{ url('invoice-details') }}"
                                                    class="{{ Request::is('invoice-details') ? 'active' : '' }}">Invoice
                                                    Details</a></li>
                                            <li><a href="{{ url('invoice-templates') }}"
                                                    class="{{ Request::is('invoice-templates') ? 'active' : '' }}">Invoice
                                                    Templates</a></li>
                                            <li><a href="{{ url('recurring-invoices') }}"
                                                    class="{{ Request::is('recurring-invoices') ? 'active' : '' }}">Recurring
                                                    Invoices</a></li>
                                        </ul>
                                    </li>
                                    <li class="{{ Request::is('credit-notes') ? 'active' : '' }}">
                                        <a href="{{ url('credit-notes') }}">
                                            <i class="isax isax-note5"></i><span>Credit Notes</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ Request::is('quotations', 'add-quotation', 'edit-quotation') ? 'active' : '' }}">
                                        <a href="{{ url('quotations') }}">
                                            <i class="isax isax-strongbox5"></i><span>Quotations</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ Request::is('delivery-challans', 'add-delivery-challan', 'edit-delivery-challan') ? 'active' : '' }}">
                                        <a href="{{ url('delivery-challans') }}">
                                            <i class="isax isax-bookmark-25"></i><span>Delivery Challans</span>
                                        </a>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('customers', 'add-customer', 'edit-customer', 'customer-details') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-profile-2user5"></i><span>Customers</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('customers') }}"
                                                    class="{{ Request::is('customers', 'add-customer', 'edit-customer') ? 'active' : '' }}">Customers</a>
                                            </li>
                                            <li><a href="{{ url('customer-details') }}"
                                                    class="{{ Request::is('customer-details') ? 'active' : '' }}">Customer
                                                    Details</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-title"><span>Purchases</span></li>
                            <li>
                                <ul>
                                    <li
                                        class="{{ Request::is('purchases', 'add-purchases', 'edit-purchases') ? 'active' : '' }}">
                                        <a href="{{ url('purchases') }}">
                                            <i class="isax isax-bag-tick-25"></i><span>Purchases</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ Request::is('purchase-orders', 'add-purchases-orders', 'edit-purchases-orders') ? 'active' : '' }}">
                                        <a href="{{ url('purchase-orders') }}">
                                            <i class="isax isax-document-forward5"></i><span>Purchase Orders</span>
                                        </a>
                                    </li>
                                    <li
                                        class="{{ Request::is('debit-notes', 'add-debit-notes', 'edit-debit-notes') ? 'active' : '' }}">
                                        <a href="{{ url('debit-notes') }}">
                                            <i class="isax isax-document-text5"></i><span>Debit Notes</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('suppliers') ? 'active' : '' }}">
                                        <a href="{{ url('suppliers') }}">
                                            <i class="isax isax-security-user5"></i><span>Suppliers</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('supplier-payments') ? 'active' : '' }}">
                                        <a href="{{ url('supplier-payments') }}">
                                            <i class="isax isax-coin-15"></i><span>Supplier Payments</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-title"><span>Finance & Accounts</span></li>
                            <li>
                                <ul>
                                    <li class="{{ Request::is('expenses') ? 'active' : '' }}">
                                        <a href="{{ url('expenses') }}">
                                            <i class="isax isax-money-send5"></i><span>Expenses</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('incomes') ? 'active' : '' }}">
                                        <a href="{{ url('incomes') }}">
                                            <i class="isax isax-money-recive5"></i><span>Incomes</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('payments') ? 'active' : '' }}">
                                        <a href="{{ url('payments') }}">
                                            <i class="isax isax-money-tick5"></i><span>Payments</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('transactions') ? 'active' : '' }}">
                                        <a href="{{ url('transactions') }}">
                                            <i class="isax isax-moneys5"></i><span>Transactions</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('bank-accounts') ? 'active' : '' }}">
                                        <a href="{{ url('bank-accounts') }}">
                                            <i class="isax isax-card-tick-15"></i><span>Bank Accounts</span>
                                        </a>
                                    </li>
                                    <li class="{{ Request::is('money-transfer') ? 'active' : '' }}">
                                        <a href="{{ url('money-transfer') }}">
                                            <i class="isax isax-convert-card5"></i><span>Money Transfer</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-title"><span>Manage</span></li>
                            <li>
                                <ul>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ request()->routeIs('bo.access.*') || Request::is('users', 'delete-account-request') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-profile-2user5"></i><span>Manage Users</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('users') }}"
                                                    class="{{ Request::is('users') ? 'active' : '' }}">Users</a></li>
                                            <li><a href="{{ route('bo.access.roles.index') }}"
                                                    class="{{ request()->routeIs('bo.access.*') ? 'active' : '' }}">Roles
                                                    & Permissions</a></li>
                                            <li><a href="{{ url('delete-account-request') }}"
                                                    class="{{ Request::is('delete-account-request') ? 'active' : '' }}">Delete
                                                    Account Request</a></li>
                                        </ul>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('membership-plans', 'membership-addons', 'subscribers', 'membership-transactions') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-star-15"></i><span>Membership</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('membership-plans') }}"
                                                    class="{{ Request::is('membership-plans') ? 'active' : '' }}">Membership
                                                    Plans</a></li>
                                            <li><a href="{{ url('membership-addons') }}"
                                                    class="{{ Request::is('membership-addons') ? 'active' : '' }}">Membership
                                                    Addons</a></li>
                                            <li><a href="{{ url('subscribers') }}"
                                                    class="{{ Request::is('subscribers') ? 'active' : '' }}">Subscribers</a>
                                            </li>
                                            <li><a href="{{ url('membership-transactions') }}"
                                                    class="{{ Request::is('membership-transactions') ? 'active' : '' }}">Transactions</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="{{ Request::is('contact-messages') ? 'active' : '' }}">
                                        <a href="{{ url('contact-messages') }}">
                                            <i class="isax isax-messages-25"></i><span>Contact Messages</span>
                                        </a>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('tickets', 'ticket-kanban', 'ticket-details') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-ticket-25"></i><span>Tickets</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li><a href="{{ url('tickets') }}"
                                                    class="{{ Request::is('tickets') ? 'active' : '' }}">Ticket
                                                    Lists</a></li>
                                            <li><a href="{{ url('ticket-kanban') }}"
                                                    class="{{ Request::is('ticket-kanban') ? 'active' : '' }}">Ticket
                                                    Kanban</a></li>
                                            <li><a href="{{ url('ticket-details') }}"
                                                    class="{{ Request::is('ticket-details') ? 'active' : '' }}">Ticket
                                                    Details</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-title"><span>Administration</span></li>
                            <li>
                                <ul>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('stock-summary', 'inventory-report', 'best-seller', 'low-stock', 'stock-history', 'sold-stock', 'sales-report', 'sales-returns', 'sales-orders', 'purchases-report', 'purchase-return-report', 'purchase-orders-report', 'quotation-report', 'payment-summary', 'tax-report', 'expense-report', 'income-report', 'profit-loss-report', 'annual-report', 'balance-sheet', 'trial-balance', 'cash-flow', 'account-statement', 'customers-report', 'customer-due-report', 'supplier-report') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-chart-35"></i><span>Reports</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('stock-summary', 'inventory-report', 'best-seller', 'low-stock', 'stock-history', 'sold-stock') ? 'active subdrop' : '' }}">Item
                                                    Reports<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('stock-summary') }}"
                                                            class="{{ Request::is('stock-summary') ? 'active' : '' }}">Stock
                                                            Summary</a></li>
                                                    <li><a href="{{ url('inventory-report') }}"
                                                            class="{{ Request::is('inventory-report') ? 'active' : '' }}">Inventory</a>
                                                    </li>
                                                    <li><a href="{{ url('best-seller') }}"
                                                            class="{{ Request::is('best-seller') ? 'active' : '' }}">Best
                                                            Seller</a></li>
                                                    <li><a href="{{ url('low-stock') }}"
                                                            class="{{ Request::is('low-stock') ? 'active' : '' }}">Low
                                                            Stock</a></li>
                                                    <li><a href="{{ url('stock-history') }}"
                                                            class="{{ Request::is('stock-history') ? 'active' : '' }}">Stock
                                                            History</a></li>
                                                    <li><a href="{{ url('sold-stock') }}"
                                                            class="{{ Request::is('sold-stock') ? 'active' : '' }}">Sold
                                                            Stock</a></li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('sales-report', 'sales-returns', 'sales-orders', 'purchases-report', 'purchase-return-report', 'purchase-orders-report', 'quotation-report') ? 'active subdrop' : '' }}">Transaction
                                                    Reports<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('sales-report') }}"
                                                            class="{{ Request::is('sales-report') ? 'active' : '' }}">Sales</a>
                                                    </li>
                                                    <li><a href="{{ url('sales-returns') }}"
                                                            class="{{ Request::is('sales-returns') ? 'active' : '' }}">Sales
                                                            Return</a></li>
                                                    <li><a href="{{ url('sales-orders') }}"
                                                            class="{{ Request::is('sales-orders') ? 'active' : '' }}">Sales
                                                            Orders</a></li>
                                                    <li><a href="{{ url('purchases-report') }}"
                                                            class="{{ Request::is('purchases-report') ? 'active' : '' }}">Purchases</a>
                                                    </li>
                                                    <li><a href="{{ url('purchase-return-report') }}"
                                                            class="{{ Request::is('purchase-return-report') ? 'active' : '' }}">Purchase
                                                            Return</a></li>
                                                    <li><a href="{{ url('purchase-orders-report') }}"
                                                            class="{{ Request::is('purchase-orders-report') ? 'active' : '' }}">Purchase
                                                            Orders</a></li>
                                                    <li><a href="{{ url('quotation-report') }}"
                                                            class="{{ Request::is('quotation-report') ? 'active' : '' }}">Quotation</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('payment-summary', 'tax-report') ? 'active subdrop' : '' }}">Finance
                                                    Reports<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('payment-summary') }}"
                                                            class="{{ Request::is('payment-summary') ? 'active' : '' }}">Payment
                                                            Summary</a></li>
                                                    <li><a href="{{ url('tax-report') }}"
                                                            class="{{ Request::is('tax-report') ? 'active' : '' }}">Taxes</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('expense-report', 'income-report', 'profit-loss-report', 'annual-report', 'balance-sheet', 'trial-balance', 'cash-flow', 'account-statement') ? 'active subdrop' : '' }}">Accounting
                                                    Reports<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('expense-report') }}"
                                                            class="{{ Request::is('expense-report') ? 'active' : '' }}">Expenses</a>
                                                    </li>
                                                    <li><a href="{{ url('income-report') }}"
                                                            class="{{ Request::is('income-report') ? 'active' : '' }}">Income</a>
                                                    </li>
                                                    <li><a href="{{ url('profit-loss-report') }}"
                                                            class="{{ Request::is('profit-loss-report') ? 'active' : '' }}">Profit
                                                            & Loss</a></li>
                                                    <li><a href="{{ url('annual-report') }}"
                                                            class="{{ Request::is('annual-report') ? 'active' : '' }}">Annual
                                                            Report</a></li>
                                                    <li><a href="{{ url('balance-sheet') }}"
                                                            class="{{ Request::is('balance-sheet') ? 'active' : '' }}">Balance
                                                            Sheet</a></li>
                                                    <li><a href="{{ url('trial-balance') }}"
                                                            class="{{ Request::is('trial-balance') ? 'active' : '' }}">Trial
                                                            Balance</a></li>
                                                    <li><a href="{{ url('cash-flow') }}"
                                                            class="{{ Request::is('cash-flow') ? 'active' : '' }}">Cash
                                                            Flow</a></li>
                                                    <li><a href="{{ url('account-statement') }}"
                                                            class="{{ Request::is('account-statement') ? 'active' : '' }}">Account
                                                            Statement</a></li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('customers-report', 'customer-due-report', 'supplier-report') ? 'active subdrop' : '' }}">User
                                                    Reports<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li>
                                                        <a href="{{ url('customers-report') }}"
                                                            class="{{ Request::is('customers-report') ? 'active' : '' }}">Customers</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('customer-due-report') }}"
                                                            class="{{ Request::is('customer-due-report') ? 'active' : '' }}">Customer
                                                            Due Report</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('supplier-report') }}"
                                                            class="{{ Request::is('supplier-report') ? 'active' : '' }}">Supplier</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="submenu">
                                        <a href="javascript:void(0);"
                                            class="{{ Request::is('account-settings', 'security-settings', 'plans-settings', 'notifications-settings', 'integrations-settings', 'company-settings', 'localization-settings', 'prefixes-settings', 'seo-setup', 'language-settings', 'language-setting2', 'language-setting3', 'maintenance-mode', 'authentication-settings', 'ai-configuration', 'appearance-settings', 'plugin-manager', 'invoice-settings', 'invoice-templates-settings', 'esignatures', 'barcode-settings', 'thermal-printer', 'custom-fields', 'sass-settings', 'email-settings', 'email-templates', 'sms-gateways', 'gdpr-cookies', 'payment-methods', 'bank-accounts-settings', 'tax-rates', 'currencies', 'custom-css', 'custom-js', 'sitemap', 'clear-cache', 'storage', 'cronjob', 'system-backup', 'database-backup', 'system-update') ? 'active subdrop' : '' }}">
                                            <i class="isax isax-setting-25"></i><span>Settings</span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <ul>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('account-settings', 'security-settings', 'plans-settings', 'notifications-settings', 'integrations-settings') ? 'active subdrop' : '' }}">General
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('account-settings') }}"
                                                            class="{{ Request::is('account-settings') ? 'active' : '' }}">Account
                                                            Settings</a></li>
                                                    <li><a href="{{ url('security-settings') }}"
                                                            class="{{ Request::is('security-settings') ? 'active' : '' }}">Security</a>
                                                    </li>
                                                    <li><a href="{{ url('plans-billings') }}"
                                                            class="{{ Request::is('plans-billings') ? 'active' : '' }}">Plans
                                                            & Billing</a></li>
                                                    <li><a href="{{ url('notifications-settings') }}"
                                                            class="{{ Request::is('notifications-settings') ? 'active' : '' }}">Notifications</a>
                                                    </li>
                                                    <li><a href="{{ url('integrations-settings') }}"
                                                            class="{{ Request::is('integrations-settings') ? 'active' : '' }}">Integrations</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('company-settings', 'localization-settings', 'prefixes-settings', 'seo-setup', 'language-settings', 'language-setting2', 'language-setting3', 'maintenance-mode', 'authentication-settings', 'ai-configuration', 'appearance-settings', 'plugin-manager') ? 'active subdrop' : '' }}">Website
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('company-settings') }}"
                                                            class="{{ Request::is('company-settings') ? 'active' : '' }}">Company
                                                            Settings</a></li>
                                                    <li><a href="{{ url('localization-settings') }}"
                                                            class="{{ Request::is('localization-settings') ? 'active' : '' }}">Localization</a>
                                                    </li>
                                                    <li><a href="{{ url('prefixes-settings') }}"
                                                            class="{{ Request::is('prefixes-settings') ? 'active' : '' }}">Prefixes</a>
                                                    </li>
                                                    <li><a href="{{ url('preference-settings') }}"
                                                            class="{{ Request::is('preference-settings') ? 'active' : '' }}">Preference</a>
                                                    </li>
                                                    <li><a href="{{ url('seo-setup') }}"
                                                            class="{{ Request::is('seo-setup') ? 'active' : '' }}">SEO
                                                            Setup</a></li>
                                                    <li><a href="{{ url('language-settings') }}"
                                                            class="{{ Request::is('language-settings', 'language-setting2', 'language-setting3') ? 'active' : '' }}">Language</a>
                                                    </li>
                                                    <li><a href="{{ url('maintenance-mode') }}"
                                                            class="{{ Request::is('maintenance-mode') ? 'active' : '' }}">Maintenance
                                                            Mode</a></li>
                                                    <li><a href="{{ url('authentication-settings') }}"
                                                            class="{{ Request::is('authentication-settings') ? 'active' : '' }}">Authentication</a>
                                                    </li>
                                                    <li><a href="{{ url('ai-configuration') }}"
                                                            class="{{ Request::is('ai-configuration') ? 'active' : '' }}">AI
                                                            Configuration</a></li>
                                                    <li><a href="{{ url('appearance-settings') }}"
                                                            class="{{ Request::is('appearance-settings') ? 'active' : '' }}">Appearance</a>
                                                    </li>
                                                    <li><a href="{{ url('plugin-manager') }}"
                                                            class="{{ Request::is('plugin-manager') ? 'active' : '' }}">Plugin
                                                            Manager</a></li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('invoice-settings', 'invoice-templates-settings', 'esignatures', 'barcode-settings', 'thermal-printer', 'custom-fields', 'sass-settings') ? 'active subdrop' : '' }}">App
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('invoice-settings') }}"
                                                            class="{{ Request::is('invoice-settings') ? 'active' : '' }}">Invoice
                                                            Settings</a></li>
                                                    <li><a href="{{ url('invoice-templates-settings') }}"
                                                            class="{{ Request::is('invoice-templates-settings') ? 'active' : '' }}">Invoice
                                                            Templates</a></li>
                                                    <li><a href="{{ url('esignatures') }}"
                                                            class="{{ Request::is('esignatures') ? 'active' : '' }}">eSignatures</a>
                                                    </li>
                                                    <li><a href="{{ url('barcode-settings') }}"
                                                            class="{{ Request::is('barcode-settings') ? 'active' : '' }}">Barcode</a>
                                                    </li>
                                                    <li><a href="{{ url('thermal-printer') }}"
                                                            class="{{ Request::is('thermal-printer') ? 'active' : '' }}">Thermal
                                                            Printer</a></li>
                                                    <li><a href="{{ url('custom-fields') }}"
                                                            class="{{ Request::is('custom-fields') ? 'active' : '' }}">Custom
                                                            Fields</a></li>
                                                    <li><a href="{{ url('sass-settings') }}"
                                                            class="{{ Request::is('sass-settings') ? 'active' : '' }}">SaaS
                                                            Settings</a></li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('email-settings', 'email-templates', 'sms-gateways', 'gdpr-cookies') ? 'active subdrop' : '' }}">System
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li><a href="{{ url('email-settings') }}"
                                                            class="{{ Request::is('email-settings') ? 'active' : '' }}">Email
                                                            Settings</a></li>
                                                    <li><a href="{{ url('email-templates') }}"
                                                            class="{{ Request::is('email-templates') ? 'active' : '' }}">Email
                                                            Templates</a></li>
                                                    <li><a href="{{ url('sms-gateways') }}"
                                                            class="{{ Request::is('sms-gateways') ? 'active' : '' }}">SMS
                                                            Gateways</a></li>
                                                    <li><a href="{{ url('gdpr-cookies') }}"
                                                            class="{{ Request::is('gdpr-cookies') ? 'active' : '' }}">GDPR
                                                            Cookies</a></li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('payment-methods', 'bank-accounts-settings', 'tax-rates', 'currencies') ? 'active subdrop' : '' }}">Finance
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li>
                                                        <a href="{{ url('payment-methods') }}"
                                                            class="{{ Request::is('payment-methods') ? 'active' : '' }}">Payment
                                                            Methods</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('bank-accounts-settings') }}"
                                                            class="{{ Request::is('bank-accounts-settings') ? 'active' : '' }}">Bank
                                                            Accounts</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('tax-rates') }}"
                                                            class="{{ Request::is('tax-rates') ? 'active' : '' }}">Tax
                                                            Rates</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('currencies') }}"
                                                            class="{{ Request::is('currencies') ? 'active' : '' }}">Currencies</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="submenu submenu-two">
                                                <a href="javascript:void(0);"
                                                    class="{{ Request::is('custom-css', 'custom-js', 'sitemap', 'clear-cache', 'storage', 'cronjob', 'system-backup', 'database-backup', 'system-update') ? 'active subdrop' : '' }}">Other
                                                    Settings<span class="menu-arrow"></span></a>
                                                <ul>
                                                    <li>
                                                        <a href="{{ url('custom-css') }}"
                                                            class="{{ Request::is('custom-css') ? 'active' : '' }}">Custom
                                                            CSS</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('custom-js') }}"
                                                            class="{{ Request::is('custom-js') ? 'active' : '' }}">Custom
                                                            JS</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('sitemap') }}"
                                                            class="{{ Request::is('sitemap') ? 'active' : '' }}">Sitemap</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('clear-cache') }}"
                                                            class="{{ Request::is('clear-cache') ? 'active' : '' }}">Clear
                                                            Cache</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('storage') }}"
                                                            class="{{ Request::is('storage') ? 'active' : '' }}">Storage</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('cronjob') }}"
                                                            class="{{ Request::is('cronjob') ? 'active' : '' }}">Cronjob</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('system-backup') }}"
                                                            class="{{ Request::is('system-backup') ? 'active' : '' }}">System
                                                            Backup</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('database-backup') }}"
                                                            class="{{ Request::is('database-backup') ? 'active' : '' }}">Database
                                                            Backup</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ url('system-update') }}"
                                                            class="{{ Request::is('system-update') ? 'active' : '' }}">System
                                                            Update</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    @endif

                    <div class="sidebar-footer">
                        <div class="trial-item bg-white text-center border">
                            <div class="bg-light p-3 text-center">
                                <img src="{{ URL::asset('build/img/icons/upgrade.svg') }}" alt="img">
                            </div>
                            <div class="p-2">
                                <h6 class="fs-14 fw-semibold mb-1">Upgrade to More</h6>
                                <p class="fs-13 mb-2">Subscribe to get more with Premium Features</p>
                                <a href="{{ url('plans-billings') }}"
                                    class="btn btn-sm btn-primary w-100 d-flex align-items-center justify-content-center"><i
                                        class="isax isax-crown5 me-1"></i>Upgrade</a>
                            </div>
                            <a href="javascript:void(0);" class="close-icon"><i class="fa-solid fa-x"></i></a>
                        </div>
                        <ul class="menu-list">
                            <li>
                                <a href="{{ url('account-settings') }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" data-bs-title="Settings"><i
                                        class="isax isax-setting-25"></i></a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="Documentation"><i class="isax isax-document-normal4"></i></a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="Changelog"><i class="isax isax-cloud-change5"></i></a>
                            </li>
                            <li>
                                <a href="{{ url('login') }}" data-bs-toggle="tooltip" data-bs-placement="top"
                                    data-bs-title="Login"><i class="isax isax-login-15"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Sidenav Menu End -->
