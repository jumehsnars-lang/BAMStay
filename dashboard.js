(() => {
    "use strict";

    const root = document.getElementById("portal");
    const nav = document.getElementById("side-nav");
    const content = document.getElementById("page-content");
    const modal = document.getElementById("modal-backdrop");
    const modalContent = document.getElementById("modal-content");
    const toast = document.getElementById("toast");
    let rooms = JSON.parse(document.getElementById("room-data").textContent);
    const icons = {
        overview: "⌂", rooms: "▦", tenants: "♙", payments: "＄", cleaning: "✧",
        maintenance: "⚒", announcements: "▤", rules: "☷", inquiries: "✉",
        applications: "♧", feedback: "☆", reports: "▥", myroom: "⌂"
    };
    const adminNav = [
        ["overview", "Dashboard"], ["rooms", "Rooms & Bedspaces"], ["tenants", "Tenants"],
        ["payments", "Payments"], ["cleaning", "Cleaning"], ["maintenance", "Maintenance"],
        ["announcements", "Announcements"], ["rules", "House Rules"], ["inquiries", "Inquiries"],
        ["applications", "Applications"], ["feedback", "Feedback"], ["reports", "Reports"]
    ];
    const tenantNav = [
        ["home", "Home"], ["myroom", "My Room"], ["payments", "Rent & Payments"],
        ["cleaning", "Cleaning Schedule"], ["maintenance", "Maintenance"], ["announcements", "Announcements"],
        ["rules", "House Rules"], ["feedback", "Feedback"]
    ];
    const tenants = [
        { name: "Alyssa Santos", phone: "0917 234 5678", room: "Room 2", bed: "Bed A", date: "Jan 12, 2026", status: "Active" },
        { name: "Mikaela Reyes", phone: "0928 456 1234", room: "Room 1", bed: "Bed A", date: "Feb 03, 2026", status: "Active" },
        { name: "Sofia Mendoza", phone: "0916 812 3054", room: "Room 3", bed: "Bed B", date: "Feb 18, 2026", status: "Active" },
        { name: "Camille Dela Cruz", phone: "0998 123 7654", room: "Room 4", bed: "Bed C", date: "Mar 01, 2026", status: "Active" },
        { name: "Nicole Garcia", phone: "0917 550 1182", room: "Room 1", bed: "Bed C", date: "Mar 12, 2026", status: "Active" }
    ];
    const cleaningRows = [
        ["Mikaela Reyes", "Room 1", "Kitchen", "Oct 24, 2026", "Oct 28, 2026", "Submitted"],
        ["Sofia Mendoza", "Room 3", "Living Area", "Oct 23, 2026", "Oct 29, 2026", "Assigned"],
        ["Camille Dela Cruz", "Room 4", "CR 1 & CR 2", "Oct 20, 2026", "Oct 25, 2026", "Verified"]
    ];
    const maintenanceRows = [
        ["Sofia Mendoza", "Room 3", "Bathroom faucet is leaking", "Water drips continuously from the cold-water handle.", "Oct 26, 2026", "Pending"],
        ["Mikaela Reyes", "Room 1", "Ceiling fan making noise", "The fan in the room started rattling last night.", "Oct 24, 2026", "In-Progress"],
        ["Camille Dela Cruz", "Room 4", "Loose cabinet hinge", "Kitchen cabinet door is not closing properly.", "Oct 20, 2026", "Resolved"]
    ];
    const announcements = [
        ["Water Interruption Notice", "warning", "Please be advised that water service will be interrupted on October 30 from 9:00 AM to 12:00 NN for scheduled maintenance.", "Oct 27, 2026", "BAMStay Admin"],
        ["Welcome to Your Home", "info", "Welcome to all our new residents! Please review the house rules and contact the admin if you need assistance.", "Oct 22, 2026", "BAMStay Admin"],
        ["Community Cleaning Day", "success", "Thank you to everyone who helped keep our shared spaces fresh and welcoming this week.", "Oct 18, 2026", "BAMStay Admin"]
    ];
    const rules = [
        ["Keep shared spaces clean", "Clean up after using the kitchen, bathroom, and living area. Return shared items to their proper place."],
        ["Observe quiet hours", "Please keep noise to a minimum from 10:00 PM to 6:00 AM so everyone can rest and study."],
        ["Visitors must register", "All visitors must be approved by the admin and recorded at the front desk. Overnight guests are not allowed."],
        ["Rent due date and late fees", "Monthly rent is due on the first day of each month. A ₱100 per-day late penalty may apply after the due date."]
    ];
    const inquiries = [
        ["Jamie Cruz", "0917 123 4400", "Oct 27, 2026", "Room 2 · Bed B", "Hi! I would like to know if this bed is available for November.", "Pending"],
        ["Trisha Lim", "0920 444 7712", "Oct 26, 2026", "Room 1", "Can I schedule a visit to see the rooms this weekend?", "Responded"],
        ["Patricia Ramos", "0995 300 2881", "Oct 24, 2026", "Not selected", "Please send details about your monthly rates.", "Pending"]
    ];
    const applications = [
        ["Isabella Flores", "isabella.flores@example.com", "0917 221 0931", "Room 2", "Oct 27, 2026", "Passed · 87%", "Pending Review"],
        ["Hannah Villanueva", "hannah.v@example.com", "0928 319 5556", "Room 3", "Oct 26, 2026", "Passed · 92%", "Ready to Activate"],
        ["Bea Navarro", "bea.navarro@example.com", "0916 882 4172", "Room 1", "Oct 25, 2026", "Awaiting capture", "Awaiting Verification"]
    ];
    const reviews = [
        ["Mikaela Reyes", "Oct 24, 2026", 5, "A comfortable place to stay and the shared spaces are always welcoming."],
        ["Sofia Mendoza", "Oct 21, 2026", 4, "I feel at home here. The admin is kind and helpful."],
        ["Camille Dela Cruz", "Oct 14, 2026", 5, "Good location and a peaceful place to focus on school."]
    ];
    let role = root.dataset.initialRole || "admin";
    let activePage = role === "tenant" ? "home" : "overview";
    let activeFilter = "All";
    let selectedStars = 0;
    let toastTimer;
    let paymentRecords = [];
    let paymentTenants = [];
    let paymentFilterTimer;
    let quoteRequestId = 0;
    let paymentRequestId = 0;
    let reportRequestId = 0;

    const esc = (value) => String(value).replace(/[&<>"']/g, (char) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[char]);
    const themeToggle = document.getElementById("theme-toggle");
    function setTheme(theme, persist = false) {
        const isDark = theme === "dark";
        document.documentElement.dataset.theme = isDark ? "dark" : "light";
        themeToggle.setAttribute("aria-pressed", String(isDark));
        themeToggle.setAttribute("aria-label", `Switch to ${isDark ? "light" : "dark"} mode`);
        themeToggle.title = `Switch to ${isDark ? "light" : "dark"} mode`;
        themeToggle.querySelector(".theme-toggle-label").textContent = `${isDark ? "Light" : "Dark"} mode`;
        if (persist) {
            try {
                localStorage.setItem("bamstay-theme", isDark ? "dark" : "light");
            } catch {}
        }
    }
    const badge = (value) => `<span class="status ${value.toLowerCase().replace(/[^a-z]+/g, "-")}">${esc(value)}</span>`;
    const avatar = (name, small = false) => `<span class="${small ? "tiny-avatar" : "avatar"}">${esc(name.split(" ").map((part) => part[0]).slice(0, 2).join(""))}</span>`;
    const pageTitle = (title, subtitle, action = "") => `<div class="page-heading"><div><p class="eyebrow">${role === "admin" ? "PROPERTY MANAGEMENT" : "YOUR BAMSTAY HOME"}</p><h1>${title}</h1><p class="page-subtitle">${subtitle}</p></div><div class="heading-actions">${action}</div></div>`;
    const panel = (title, body, note = "", link = "", className = "") => `<section class="panel ${className}"><div class="panel-head"><div><h2>${title}</h2>${note ? `<p>${note}</p>` : ""}</div>${link}</div><div class="panel-body">${body}</div></section>`;
    const stat = (label, value, icon, note = "") => `<article class="stat-card"><div class="stat-top"><span>${label}</span><span class="stat-icon">${icon}</span></div><div class="stat-value">${value}</div><div class="stat-note">${note}</div></article>`;
    const initialsName = (name) => `<span class="tenant-cell">${avatar(name, true)}<strong>${esc(name)}</strong></span>`;
    const actionButton = (label, action, cls = "btn-light") => `<button class="btn ${cls} btn-sm" type="button" data-action="${action}">${label}</button>`;
    const previewNote = "";

    function renderNav() {
        const items = role === "admin" ? adminNav : tenantNav;
        nav.innerHTML = items.map(([id, label]) => {
            const key = role === "tenant" && id === "home" ? "home" : id;
            return `<button type="button" class="side-link ${activePage === key ? "active" : ""}" data-page="${key}" data-label="${esc(label)}" aria-label="${esc(label)}"><span class="nav-icon">${icons[id] || "•"}</span><span class="side-link-label">${label}</span>${id === "applications" ? '<span class="nav-count">3</span>' : ""}${id === "inquiries" ? '<span class="nav-count">2</span>' : ""}</button>`;
        }).join("");
        const label = (role === "admin" ? adminNav : tenantNav).find(([id]) => id === activePage)?.[1] || "Dashboard";
        document.getElementById("crumb-label").textContent = label;
    }

    function roomOccupancy() {
        return rooms.map((room) => {
            const percent = room.counts.total ? Math.round(room.counts.occupied / room.counts.total * 100) : 0;
            return `<div class="occupancy-row"><strong>${esc(room.name)}</strong><div class="progress-track"><div class="progress-fill" style="width:${percent}%"></div></div><small>${room.counts.occupied} / ${room.counts.total} beds</small></div>`;
        }).join("");
    }

    function renderOverview() {
        const maintenance = maintenanceRows.slice(0, 3).map((row) => `<tr><td>${initialsName(row[0])}</td><td>${esc(row[2])}</td><td>${esc(row[4])}</td><td>${row[5] === "Pending" ? "Unassigned" : "Property Staff"}</td><td>${badge(row[5])}</td><td>${actionButton("View", "maintenance-detail")}</td></tr>`).join("");
        const totalBeds = rooms.reduce((sum, room) => sum + room.counts.total, 0);
        const occupiedBeds = rooms.reduce((sum, room) => sum + room.counts.occupied, 0);
        const availableBeds = rooms.reduce((sum, room) => sum + room.counts.available, 0);
        queueMicrotask(() => loadRecentPayments());
        return `${pageTitle("Property overview", "Monitor occupancy, rent collection, and resident activity.", actionButton("＋ Add payment", "add-payment", "btn-primary"))}${previewNote}<div class="grid stats-grid">${stat("Total Rooms", String(rooms.length), "▦", "Across the property")}${stat("Total Tenants", "—", "♙", "Tenant records available in Tenants")}${stat("Occupied Beds", String(occupiedBeds), "⌂", `of ${totalBeds} total beds`)}${stat("Available Beds", String(availableBeds), "＋", `Across ${rooms.length} rooms`)}<div data-overview-payment-stat>${stat("Unpaid Payments", "—", "＄", "Loading confirmed account balances")}</div>${stat("Average Rating", "—", "☆", "No connected review records")}</div><div class="grid action-grid"><button class="action-card" data-page="cleaning"><span class="task-icon">✧</span><span><strong>Cleaning to Verify</strong><small>Cleaning records are not connected</small></span></button><button class="action-card" data-page="maintenance"><span class="task-icon">⚒</span><span><strong>Maintenance Pending</strong><small>Maintenance records are not connected</small></span></button><button class="action-card" data-page="inquiries"><span class="task-icon">✉</span><span><strong>New Inquiries</strong><small>Inquiry counts are not loaded here</small></span></button></div><div class="grid columns-2">${panel("Room occupancy", `<div data-overview-room-occupancy>${roomOccupancy()}</div>`, "Live room & bedspace availability", '<a class="text-link" href="#" data-page="rooms">Manage rooms →</a>')}${panel("Recent payments", `<div data-recent-payments class="table-wrap">Loading confirmed payment activity…</div>`, "Database-backed payment records", '<a class="text-link" href="#" data-page="payments">View all →</a>')}</div><div style="height:15px"></div>${panel("Recent maintenance", `<div class="empty-state">Maintenance records are not connected to a database.</div>`, "No live maintenance data is available")}`;
    }

    function renderRooms() {
        const cards = rooms.map((room) => {
            const percent = room.counts.total ? Math.round(room.counts.occupied / room.counts.total * 100) : 0;
            const status = room.status === "inactive" ? "Inactive" : "Active";
            const nextStatus = room.status === "inactive" ? "active" : "inactive";
            const toggleLabel = room.status === "inactive" ? "Activate" : "Deactivate";
            const matchesFilter = activeFilter === "All" || activeFilter.toLowerCase() === room.status;
            return `<article class="room-card" data-room-card data-room-status="${esc(room.status)}" data-search="${esc(`${room.room_number} ${room.name}`.toLowerCase())}" ${matchesFilter ? "" : "hidden"}><div class="room-photo"><img src="${esc(room.image)}" alt="${esc(room.name)}" loading="lazy">${badge(status)}</div><div class="room-content"><div class="room-title-row"><h3>${esc(room.room_number)}</h3><span>${room.counts.total} bedspaces</span></div><p class="room-subtitle">${esc(room.name)} · ${room.counts.occupied} occupied · ${room.counts.available} available</p><div class="progress-track"><div class="progress-fill" style="width:${percent}%"></div></div><div class="room-progress-label"><span>Occupancy</span><span>${percent}%</span></div><div class="feature-tags">${room.features.slice(0, 3).map((feature) => `<span class="feature-tag">${esc(feature)}</span>`).join("")}</div><div class="room-actions">${actionButton("View room", "room-detail", "btn-soft").replace('data-action="room-detail"', `data-action="room-detail" data-room="${esc(room.id)}"`)} ${actionButton("Edit room", "edit-room").replace('data-action="edit-room"', `data-action="edit-room" data-room="${esc(room.id)}"`)} ${actionButton(toggleLabel, "toggle-room").replace('data-action="toggle-room"', `data-action="toggle-room" data-room="${esc(room.id)}" data-status="${nextStatus}"`)}</div></div></article>`;
        }).join("");
        return `${pageTitle("Rooms & Bedspaces", "Manage room details, occupancy, and tenant assignments.", actionButton("＋ Add room", "add-room", "btn-primary"))}${previewNote}<div class="toolbar"><label class="search-box">⌕ <input type="search" placeholder="Search room number or name" data-search-input></label><div class="filter-group">${["All", "Active", "Inactive"].map((item) => `<button class="filter-button ${activeFilter === item ? "active" : ""}" type="button" data-filter="${item}">${item}</button>`).join("")}</div></div><div class="grid room-grid">${cards}</div>`;
    }

    async function loadRooms() {
        try {
            const result = await requestJson(roomsApi);
            rooms = result.rooms;
            if (activePage === "rooms") content.innerHTML = renderRooms();
            if (activePage === "overview") {
                const cards = content.querySelectorAll(".stats-grid .stat-card");
                const totalBeds = rooms.reduce((sum, room) => sum + room.counts.total, 0);
                const occupiedBeds = rooms.reduce((sum, room) => sum + room.counts.occupied, 0);
                const availableBeds = rooms.reduce((sum, room) => sum + room.counts.available, 0);
                if (cards.length >= 4) {
                    cards[0].querySelector(".stat-value").textContent = String(rooms.length);
                    cards[2].querySelector(".stat-value").textContent = String(occupiedBeds);
                    cards[2].querySelector(".stat-note").textContent = `of ${totalBeds} total beds`;
                    cards[3].querySelector(".stat-value").textContent = String(availableBeds);
                    cards[3].querySelector(".stat-note").textContent = `Across ${rooms.length} rooms`;
                }
                const occupancy = content.querySelector("[data-overview-room-occupancy]");
                if (occupancy) occupancy.innerHTML = roomOccupancy();
            }
        } catch (error) {
            if (activePage === "rooms") {
                const errorNotice = `<div class="form-error">${esc(error.message)} Import the room management migration if this is the first installation.</div>`;
                content.insertAdjacentHTML("afterbegin", errorNotice);
            }
        }
    }

    function renderTenants() {
        queueMicrotask(() => loadTenants());
        return `${pageTitle("Tenants", "Manage tenant accounts and room assignments.", actionButton("＋ Add tenant", "add-tenant", "btn-primary"))}<div class="toolbar"><label class="search-box">⌕ <input type="search" placeholder="Filter by name or contact" data-tenant-search></label></div><div data-tenant-error class="form-error" hidden></div>${panel("Tenant directory", `<div class="table-wrap"><table><thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Room</th><th>Bedspace</th><th>Move-in Date</th><th>Status</th></tr></thead><tbody data-tenant-rows><tr><td colspan="7">Loading tenant records…</td></tr></tbody></table></div>`)}`;
    }

    function renderPayments() {
        const headingActions = role === "admin"
            ? `${actionButton("＋ Add tenant", "add-tenant", "btn-light")} ${actionButton("＋ Record payment", "add-payment", "btn-primary")}`
            : actionButton("＋ Add payment", "tenant-add-payment", "btn-primary");
        const stats = `<div class="grid columns-3 payment-stats" data-payment-stats><div class="stat-card"><div class="stat-top"><span>${role === "admin" ? "Total Collected" : "Confirmed Payments"}</span><span class="stat-icon">₱</span></div><div class="stat-value">—</div><div class="stat-note">Current month</div></div><div class="stat-card"><div class="stat-top"><span>${role === "admin" ? "Outstanding Balance" : "Outstanding Balance"}</span><span class="stat-icon">◷</span></div><div class="stat-value">—</div><div class="stat-note">Unsettled rent obligations</div></div><div class="stat-card"><div class="stat-top"><span>${role === "admin" ? "Overdue Amount" : "Overdue Amount"}</span><span class="stat-icon">!</span></div><div class="stat-value">—</div><div class="stat-note" data-overdue-count>Server-calculated</div></div></div>`;
        const statuses = ["All", "Paid", "Partially Paid", "Pending", "Overdue", "Due"];
        const statusFilters = statuses.map((item) => `<button class="filter-button ${activeFilter === item ? "active" : ""}" data-payment-status="${esc(item)}" type="button">${esc(item)}</button>`).join("");
        const adminFilters = role === "admin" ? `<label class="form-field payment-month-filter">Billing month<input type="month" data-payment-month></label><label class="form-field payment-method-filter">Method<select data-payment-method><option value="">All methods</option><option>Cash</option><option>GCash</option><option>Maya</option><option>Bank Transfer</option><option>Other</option></select></label>` : "";
        const header = role === "admin"
            ? `<thead><tr><th>Receipt</th><th>Tenant</th><th>Room / Bed</th><th>Billing period</th><th>Amount due</th><th>Received</th><th>Balance</th><th>Method / Reference</th><th>Payment date</th><th>Due date</th><th>Status</th><th>Actions</th></tr></thead>`
            : `<thead><tr><th>Receipt</th><th>Billing period</th><th>Amount due</th><th>Amount received</th><th>Remaining</th><th>Method</th><th>Reference</th><th>Payment date</th><th>Due date</th><th>Status</th><th>Receipt</th></tr></thead>`;
        const policy = role === "tenant" ? `<div class="policy-banner">ⓘ Rent is due on the first day of each billing month. Any late fee must be recorded as an authorized obligation adjustment.</div>` : "";
        const contentHtml = `<div class="toolbar payment-toolbar"><label class="search-box">⌕ <input type="search" placeholder="Search tenant, receipt, reference, or billing period" data-payment-search></label><div class="filter-group payment-status-filters">${statusFilters}</div>${adminFilters}</div><div data-payment-error class="form-error" hidden></div><div class="table-wrap payment-table-wrap"><table class="payment-management-table">${header}<tbody data-payment-rows><tr><td colspan="12">Loading payment records…</td></tr></tbody></table></div>`;
        const submissionTable = role === "admin"
            ? `<div data-submission-error class="form-error" hidden></div><div class="table-wrap"><table class="payment-submissions-table"><thead><tr><th>Tenant</th><th>Billing period</th><th>Amount</th><th>Method / Reference</th><th>Payment date</th><th>Submitted</th><th>Status</th><th>Review</th></tr></thead><tbody data-payment-submissions><tr><td colspan="8">Loading tenant payment requests…</td></tr></tbody></table></div>`
            : `<div data-submission-error class="form-error" hidden></div><div class="table-wrap"><table class="payment-submissions-table"><thead><tr><th>Billing period</th><th>Amount</th><th>Method / Reference</th><th>Payment date</th><th>Submitted</th><th>Status</th><th>Review note</th></tr></thead><tbody data-payment-submissions><tr><td colspan="7">Loading your payment requests…</td></tr></tbody></table></div>`;
        queueMicrotask(() => loadPayments());
        return `${pageTitle(role === "admin" ? "Payments" : "Rent & Payments", role === "admin" ? "Record verified receipts and monitor outstanding rent obligations." : "Review your rent balance, submit a payment, and track its verification.", headingActions)}${stats}${policy}${role === "admin" ? `${panel("Tenant payment requests", submissionTable, "Requests remain pending and do not count as collected rent until reviewed.")}${panel("Payment records", contentHtml, "Amounts due are compared with confirmed payments; pending transfers are not counted as collections.")}` : `${panel("My payment requests", submissionTable, "Requests remain pending until an administrator verifies the payment.")}${panel("Payment history", contentHtml, "Only verified payments count toward your rent balance.")}`}`;
    }

    const paymentApi = root.dataset.paymentApi;
    const tenantApi = root.dataset.tenantApi;
    const tenantPortalApi = root.dataset.tenantPortalApi;
    const roomsApi = root.dataset.roomsApi;
    const roomPhotoApi = root.dataset.roomPhotoApi;
    const gcashQrUrl = root.dataset.gcashQrUrl || "";
    const csrfToken = root.dataset.csrfToken;
    const formatMoney = (value) => new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP", minimumFractionDigits: 2 }).format(Number(value) || 0);
    const localDate = () => {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
    };
    const formatDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" }) : "—";

    async function requestJson(url, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set("Accept", "application/json");
        if (options.body) {
            headers.set("Content-Type", "application/json");
            headers.set("X-CSRF-Token", csrfToken);
        }
        const response = await fetch(url, { ...options, headers, cache: "no-store" });
        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error("The server returned an unreadable response. Please try again.");
        }
        if (!response.ok) {
            const error = new Error(payload.error || "The request could not be completed.");
            error.field = payload.field;
            throw error;
        }
        return payload;
    }

    function showFormError(form, error) {
        const general = form.querySelector("[data-form-error]");
        if (error.field && form.elements.namedItem(error.field)) {
            const control = form.elements.namedItem(error.field);
            const wrapper = control.closest(".form-field");
            let fieldError = wrapper?.querySelector("[data-field-error]");
            if (wrapper && !fieldError) {
                fieldError = document.createElement("small");
                fieldError.dataset.fieldError = "";
                fieldError.className = "field-error";
                wrapper.append(fieldError);
            }
            if (fieldError) {
                fieldError.textContent = error.message;
                control.setAttribute("aria-invalid", "true");
                return;
            }
        }
        if (general) {
            general.textContent = error.message;
            general.hidden = false;
        }
    }

    function clearFormErrors(form) {
        const general = form.querySelector("[data-form-error]");
        if (general) {
            general.textContent = "";
            general.hidden = true;
        }
        form.querySelectorAll("[data-field-error]").forEach((fieldError) => fieldError.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((control) => control.removeAttribute("aria-invalid"));
    }

    function renderPaymentRows(records) {
        if (!records.length) return `<tr><td class="empty-state" colspan="${role === "admin" ? 12 : 11}">No payment records match these filters.</td></tr>`;
        return records.map((record) => {
            const actions = role === "admin"
                ? `<button class="btn btn-light btn-sm" type="button" data-action="receipt" data-payment-id="${esc(record.id)}">Receipt</button>${record.canVerify ? ` <button class="btn btn-primary btn-sm" type="button" data-action="verify-payment" data-payment-id="${esc(record.id)}">Verify</button>` : ""}${record.canEdit ? ` <button class="icon-action" type="button" aria-label="Edit pending payment" data-action="edit-payment" data-payment-id="${esc(record.id)}">✎</button><button class="icon-action" type="button" aria-label="Void pending payment" data-action="void-payment" data-payment-id="${esc(record.id)}">×</button>` : ""}${record.canReverse ? ` <button class="btn btn-light btn-sm" type="button" data-action="reverse-payment" data-payment-id="${esc(record.id)}">Reverse</button>` : ""}`
                : `<button class="icon-action" type="button" aria-label="View receipt" data-action="receipt" data-payment-id="${esc(record.id)}">▤</button>`;
            const common = `<td><strong>${esc(record.receiptNumber)}</strong></td>`;
            if (role === "tenant") {
                return `<tr>${common}<td>${formatDate(record.billingMonth)}</td><td>${formatMoney(record.amountDue)}</td><td>${formatMoney(record.amountReceived)}</td><td>${formatMoney(record.remainingBalance)}</td><td>${esc(record.paymentMethod)}</td><td>${esc(record.referenceNumber || "—")}</td><td>${formatDate(record.paymentDate)}</td><td>${formatDate(record.dueDate)}</td><td>${badge(record.displayStatus)}</td><td>${actions}</td></tr>`;
            }
            return `<tr>${common}<td>${initialsName(record.tenantName)}</td><td>${esc(record.roomId)} · ${esc(record.bedspaceId)}</td><td>${formatDate(record.billingMonth)}</td><td>${formatMoney(record.amountDue)}</td><td>${formatMoney(record.amountReceived)}</td><td>${formatMoney(record.remainingBalance)}</td><td>${esc(record.paymentMethod)}<small class="table-subtext">${esc(record.referenceNumber || "No reference")}</small></td><td>${formatDate(record.paymentDate)}</td><td>${formatDate(record.dueDate)}</td><td>${badge(record.displayStatus)}</td><td class="payment-actions">${actions}</td></tr>`;
        }).join("");
    }

    function showPaymentError(message, selector = "[data-payment-error]") {
        const element = document.querySelector(selector);
        if (!element) return;
        element.textContent = message;
        element.hidden = false;
    }

    async function loadPayments() {
        if (activePage !== "payments") return;
        const requestId = ++paymentRequestId;
        const errorBox = content.querySelector("[data-payment-error]");
        if (errorBox) errorBox.hidden = true;
        const tableBody = content.querySelector("[data-payment-rows]");
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="${role === "admin" ? 12 : 11}">Loading payment records…</td></tr>`;
        try {
            if (role === "admin") {
                await requestJson(paymentApi, { method: "POST", body: JSON.stringify({ action: "initialize" }) });
            }
            const params = new URLSearchParams({ op: "list" });
            const search = content.querySelector("[data-payment-search]")?.value.trim();
            const month = content.querySelector("[data-payment-month]")?.value;
            const method = content.querySelector("[data-payment-method]")?.value;
            if (search) params.set("q", search);
            if (month) params.set("month", month);
            if (method) params.set("method", method);
            if (activeFilter !== "All") params.set("status", activeFilter);
            const result = await requestJson(`${paymentApi}?${params.toString()}`);
            if (requestId !== paymentRequestId || activePage !== "payments") return;
            paymentRecords = result.records;
            tableBody.innerHTML = renderPaymentRows(paymentRecords);
            const cards = content.querySelectorAll("[data-payment-stats] .stat-card");
            if (result.summary && cards.length === 3) {
                cards[0].querySelector(".stat-value").textContent = formatMoney(result.summary.totalCollected);
                cards[0].querySelector(".stat-note").textContent = role === "admin" ? "Confirmed receipts this month" : "Confirmed this month";
                cards[1].querySelector(".stat-value").textContent = formatMoney(result.summary.outstandingBalance);
                cards[2].querySelector(".stat-value").textContent = formatMoney(result.summary.overdueAmount);
                cards[2].querySelector(".stat-note").textContent = role === "admin"
                    ? `${result.summary.overdueTenantCount} tenant${result.summary.overdueTenantCount === 1 ? "" : "s"} with overdue rent`
                    : "Past-due rent balance";
            }
            await loadPaymentSubmissions();
        } catch (error) {
            if (requestId !== paymentRequestId || activePage !== "payments") return;
            tableBody.innerHTML = `<tr><td class="empty-state" colspan="${role === "admin" ? 12 : 11}">Payment data could not be loaded.</td></tr>`;
            showPaymentError(`${error.message} If this is the first installation, import the payment migration and sign in with an administrator account.`);
        }
    }

    function renderPaymentSubmissions(items) {
        const tableBody = content.querySelector("[data-payment-submissions]");
        if (!tableBody) return;
        const columns = role === "admin" ? 8 : 7;
        if (!items.length) {
            tableBody.innerHTML = `<tr><td class="empty-state" colspan="${columns}">No payment requests have been submitted.</td></tr>`;
            return;
        }
        tableBody.innerHTML = items.map((item) => {
            const method = `${esc(item.paymentMethod)}${item.referenceNumber ? `<small class="table-subtext">${esc(item.referenceNumber)}</small>` : ""}`;
            const submitted = item.createdAt ? formatDate(item.createdAt.slice(0, 10)) : "—";
            if (role === "admin") {
                const actions = item.status === "Pending"
                    ? `<div class="submission-actions"><button class="btn btn-primary btn-sm" type="button" data-action="accept-submission" data-submission-id="${esc(item.id)}">Verify &amp; accept</button><button class="btn btn-light btn-sm" type="button" data-action="decline-submission" data-submission-id="${esc(item.id)}">Decline</button></div>`
                    : `<span class="table-subtext">${esc(item.reviewedBy || "Reviewed")}${item.reviewNote ? `<br>${esc(item.reviewNote)}` : ""}</span>`;
                return `<tr><td><strong>${esc(item.tenantName)}</strong><small class="table-subtext">${esc(item.roomId)} · ${esc(item.bedspaceId)}</small></td><td>${formatDate(item.billingMonth)}</td><td>${formatMoney(item.amount)}</td><td>${method}</td><td>${formatDate(item.paymentDate)}</td><td>${submitted}</td><td>${badge(item.status)}</td><td>${actions}</td></tr>`;
            }
            return `<tr><td>${formatDate(item.billingMonth)}</td><td>${formatMoney(item.amount)}</td><td>${method}</td><td>${formatDate(item.paymentDate)}</td><td>${submitted}</td><td>${badge(item.status)}</td><td>${esc(item.reviewNote || item.reviewedBy || "Awaiting administrator review")}</td></tr>`;
        }).join("");
    }

    async function loadPaymentSubmissions() {
        const tableBody = content.querySelector("[data-payment-submissions]");
        if (!tableBody) return;
        const errorBox = content.querySelector("[data-submission-error]");
        if (errorBox) errorBox.hidden = true;
        try {
            const result = await requestJson(`${paymentApi}?op=submissions`);
            if (activePage !== "payments") return;
            renderPaymentSubmissions(result.submissions);
        } catch (error) {
            tableBody.innerHTML = `<tr><td class="empty-state" colspan="${role === "admin" ? 8 : 7}">Payment requests could not be loaded.</td></tr>`;
            if (errorBox) {
                errorBox.textContent = error.message;
                errorBox.hidden = false;
            }
        }
    }

    async function loadRecentPayments() {
        if (role !== "admin" || activePage !== "overview") return;
        const container = content.querySelector("[data-recent-payments]");
        if (!container) return;
        try {
            await requestJson(paymentApi, { method: "POST", body: JSON.stringify({ action: "initialize" }) });
            const result = await requestJson(`${paymentApi}?op=list`);
            container.innerHTML = result.records.length
                ? `<table><thead><tr><th>Receipt</th><th>Tenant</th><th>Billing period</th><th>Amount</th><th>Status</th></tr></thead><tbody>${result.records.slice(0, 3).map((record) => `<tr><td>${esc(record.receiptNumber)}</td><td>${esc(record.tenantName)}</td><td>${formatDate(record.billingMonth)}</td><td>${formatMoney(record.amountReceived)}</td><td>${badge(record.displayStatus)}</td></tr>`).join("")}</tbody></table>`
                : `<div class="empty-state">No payments have been recorded.</div>`;
            const unpaid = content.querySelector("[data-overview-payment-stat] .stat-value");
            const note = content.querySelector("[data-overview-payment-stat] .stat-note");
            if (unpaid && result.summary) {
                unpaid.textContent = String(result.summary.pendingCount + result.summary.overdueTenantCount);
                note.textContent = `${result.summary.pendingCount} pending · ${result.summary.overdueTenantCount} overdue`;
            }
            const tenantSummary = await requestJson(tenantApi);
            const tenantCard = [...content.querySelectorAll(".stat-card")].find((card) => card.querySelector(".stat-top")?.textContent.includes("Total Tenants"));
            if (tenantCard) tenantCard.querySelector(".stat-value").textContent = String(tenantSummary.tenants.filter((tenant) => tenant.status === "active").length);
        } catch (error) {
            container.innerHTML = `<div class="empty-state">${esc(error.message)}</div>`;
        }
    }

    function tenantTableRows(items) {
        if (!items.length) return `<tr><td class="empty-state" colspan="7">No tenant accounts are set up yet. Add a tenant to create their account and assign a bed.</td></tr>`;
        return items.map((tenant) => `<tr data-tenant-row data-search="${esc(`${tenant.full_name} ${tenant.phone} ${tenant.email}`.toLowerCase())}"><td>${initialsName(tenant.full_name)}</td><td>${esc(tenant.phone)}</td><td>${esc(tenant.email)}</td><td>${esc(tenant.room_name)}</td><td>${esc(tenant.bed_label)}</td><td>${formatDate(tenant.move_in_date)}</td><td>${badge(tenant.status)}</td></tr>`).join("");
    }

    async function loadTenants() {
        if (activePage !== "tenants") return;
        const rows = content.querySelector("[data-tenant-rows]");
        const error = content.querySelector("[data-tenant-error]");
        if (!rows) return;
        error.hidden = true;
        try {
            const result = await requestJson(tenantApi);
            rows.innerHTML = tenantTableRows(result.tenants);
        } catch (exception) {
            rows.innerHTML = `<tr><td colspan="7">Tenant records could not be loaded.</td></tr>`;
            error.textContent = exception.message;
            error.hidden = false;
        }
    }

    async function loadTenantPortal() {
        if (role !== "tenant" || !["home", "myroom"].includes(activePage)) return;
        const errorBox = content.querySelector("[data-tenant-profile-error]");
        if (errorBox) errorBox.hidden = true;
        try {
            const result = await requestJson(tenantPortalApi);
            const tenant = result.tenant;
            const firstName = tenant.fullName.trim().split(/\s+/)[0];
            if (activePage === "home") {
                const heading = content.querySelector("h1");
                if (heading) heading.textContent = `Welcome back, ${firstName}`;
                const values = content.querySelectorAll(".tenant-home-stats .stat-value");
                const notes = content.querySelectorAll(".tenant-home-stats .stat-note");
                values[0].textContent = tenant.roomName;
                values[1].textContent = tenant.bedspaceLabel;
                values[2].textContent = result.latestPayment
                    ? `${result.latestPayment.paymentStatus === "pending" ? "Pending" : formatMoney(result.latestPayment.confirmedAmount)}`
                    : "No payments";
                values[3].textContent = formatDate(tenant.moveInDate);
                notes[0].textContent = "Assigned room";
                notes[1].textContent = "Assigned bedspace";
                notes[2].textContent = result.latestPayment ? `Billing ${formatDate(result.latestPayment.billingMonth)}` : "No payment recorded";
                notes[3].textContent = "Move-in date";
            } else {
                const room = result.room;
                const beds = room.beds.map((bed) => `<div class="bed-tile ${bed.isYou ? "you" : ""}">${bed.image ? `<img class="bedspace-photo" src="${esc(bed.image)}" alt="${esc(bed.label)}" loading="lazy">` : ""}<strong>${esc(bed.label)}</strong><small>${bed.isYou ? `${esc(tenant.fullName)} · You` : esc(bed.tenantName || bed.status)}</small>${badge(bed.status)}</div>`).join("");
                const roommates = room.beds.filter((bed) => bed.tenantName && !bed.isYou).map((bed) => `<div class="task-item">${avatar(bed.tenantName, true)}<div class="task-copy"><strong>${esc(bed.tenantName)}</strong><small>${esc(bed.label)}</small></div>${badge("Active")}</div>`).join("");
                content.querySelector("[data-myroom-content]").innerHTML = `<div class="grid columns-2"><section class="panel"><img src="${esc(room.image)}" alt="${esc(room.name)}" style="width:100%;height:230px;object-fit:cover"><div class="panel-body" style="padding-top:15px"><div class="card-top"><div><h2 class="section-title">${esc(room.name)}</h2><p class="page-subtitle">${esc(room.description)}</p></div>${badge("Active")}</div><div class="feature-tags">${room.features.map((feature) => `<span class="feature-tag">${esc(feature)}</span>`).join("")}</div><div class="detail-list" style="margin-top:15px"><div class="detail-item"><small>Bedspace</small><strong>${esc(tenant.bedspaceLabel)} · Occupied</strong></div><div class="detail-item"><small>Move-in date</small><strong>${formatDate(tenant.moveInDate)}</strong></div><div class="detail-item"><small>Monthly rent</small><strong>${formatMoney(tenant.monthlyRent)}</strong></div><div class="detail-item"><small>Roommates</small><strong>${roommates.length}</strong></div></div></div></section><div class="grid" style="align-content:start">${panel("Room layout", `<div class="room-layout">${beds}</div>`, "Your bedspace is highlighted")}${panel("Your roommates", roommates.length ? `<div class="task-list">${roommates}</div>` : `<div class="empty-state">No other active tenants are assigned to this room.</div>`)}</div></div>`;
            }
        } catch (error) {
            if (errorBox) {
                errorBox.textContent = error.message;
                errorBox.hidden = false;
            }
        }
    }

    async function openTenantModal() {
        try {
            const result = await requestJson(tenantApi);
            const roomsWithBeds = result.rooms;
            const roomOptions = roomsWithBeds.map((room) => `<option value="${esc(room.id)}" data-rent="${esc(room.monthlyRent ?? "")}">${esc(room.name)}</option>`).join("");
            const bedOptions = (room) => room ? room.beds.map((bed) => `<option value="${esc(bed.id)}">${esc(bed.label)}</option>`).join("") : "";
            openModal("Add tenant account", `<form data-tenant-form><div class="form-grid"><label class="form-field">Full name<input name="fullName" required maxlength="150" autocomplete="name"></label><label class="form-field">Contact number<input name="phone" required autocomplete="tel" placeholder="09171234567"></label><label class="form-field">Emergency contact<input name="emergencyContact" maxlength="150" placeholder="Name · contact number"></label><label class="form-field">Login email<input name="email" type="email" required maxlength="254" autocomplete="email"></label><label class="form-field">Room<select name="roomId" data-tenant-room required><option value="">Select available room</option>${roomOptions}</select></label><label class="form-field">Bedspace<select name="bedspaceId" data-tenant-bed required><option value="">Select available bed</option>${bedOptions(roomsWithBeds[0])}</select></label><label class="form-field">Move-in date<input name="moveInDate" type="date" value="${localDate()}" required></label><label class="form-field">Monthly rent<input name="monthlyRent" type="number" min="0.01" step="0.01" value="${esc(roomsWithBeds[0]?.monthlyRent ?? "1800.00")}" required></label></div><p class="form-hint">A temporary password will be generated. The tenant will be required to change it at first sign-in.</p><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">Create tenant</button></div></form>`, true);
            modalContent.querySelector("[data-tenant-room]")?.addEventListener("change", (event) => {
                const room = roomsWithBeds.find((item) => item.id === event.target.value);
                const bedSelect = modalContent.querySelector("[data-tenant-bed]");
                bedSelect.innerHTML = `<option value="">Select available bed</option>${bedOptions(room)}`;
                const rentInput = modalContent.querySelector('[name="monthlyRent"]');
                if (room && room.monthlyRent !== null) rentInput.value = room.monthlyRent;
            });
        } catch (error) {
            openModal("Tenant accounts unavailable", `<div class="form-error">${esc(error.message)}</div>`);
        }
    }

    async function updatePaymentQuote() {
        const form = modalContent.querySelector("[data-payment-form]");
        if (!form) return;
        const tenantId = form.elements.tenantId.value;
        const month = form.elements.billingMonth.value;
        if (!tenantId || !month) return;
        const quoteError = form.querySelector("[data-quote-error]");
        const requestId = ++quoteRequestId;
        quoteError.hidden = true;
        form.querySelector('[type="submit"]').disabled = true;
        try {
            const quote = await requestJson(`${paymentApi}?op=quote&tenantId=${encodeURIComponent(tenantId)}&month=${encodeURIComponent(month)}`);
            if (requestId !== quoteRequestId || !form.isConnected) return;
            form.dataset.available = quote.availableToRecord;
            form.querySelector("[data-rent-due]").textContent = formatMoney(quote.rentDue);
            form.querySelector("[data-previous-balance]").textContent = formatMoney(quote.previousBalance);
            form.querySelector("[data-payment-due-date]").textContent = formatDate(quote.dueDate);
            form.querySelector('[name="amountReceived"]').value = quote.availableToRecord;
            const hasBalance = Math.round(Number(quote.availableToRecord) * 100) > 0;
            form.querySelector('[type="submit"]').disabled = !hasBalance;
            if (!hasBalance) {
                quoteError.textContent = "There is no unreserved balance available to record for this billing month.";
                quoteError.hidden = false;
            }
            updatePaymentEstimate(form);
        } catch (error) {
            if (requestId !== quoteRequestId || !form.isConnected) return;
            quoteError.textContent = error.message;
            quoteError.hidden = false;
        }
    }

    function updatePaymentEstimate(form) {
        const amount = Math.round((Number(form.elements.amountReceived.value) || 0) * 100);
        const available = Math.round((Number(form.dataset.available) || 0) * 100);
        const remaining = Math.max(0, available - amount);
        form.querySelector("[data-estimated-remaining]").textContent = formatMoney((remaining / 100).toFixed(2));
        const method = form.elements.paymentMethod.value;
        const confirmedCash = method === "Cash" && form.elements.confirmCashReceived.checked;
        const status = amount <= 0 ? "—" : method === "Cash" && confirmedCash
            ? remaining === 0 ? "Paid after confirmation" : "Partially paid after confirmation"
            : "Pending verification";
        form.querySelector("[data-estimated-status]").textContent = status;
    }

    async function openPaymentModal() {
        try {
            const result = await requestJson(tenantApi);
            paymentTenants = result.tenants.filter((tenant) => tenant.status === "active");
            if (paymentTenants.length === 0) {
                openModal("No active tenants", `<p class="card-copy">Add an active tenant account and assign a bedspace before recording rent.</p><div class="modal-actions"><button class="btn btn-light" type="button" data-close>Close</button><button class="btn btn-primary" type="button" data-action="add-tenant">Add tenant</button></div>`);
                return;
            }
            const options = paymentTenants.map((tenant) => `<option value="${tenant.id}">${esc(tenant.full_name)} · ${esc(tenant.room_name)} · ${esc(tenant.bed_label)}</option>`).join("");
            const month = localDate().slice(0, 7);
            openModal("Record rent payment", `<form data-payment-form data-available="0" data-idempotency-key="${crypto.randomUUID()}"><div class="payment-form-section"><h3>Tenant & billing</h3><div class="form-grid"><label class="form-field full">Tenant<select name="tenantId" required>${options}</select></label><label class="form-field">Billing month<input name="billingMonth" type="month" value="${month}" required></label><div class="payment-quote-item"><small>Due date</small><strong data-payment-due-date>—</strong></div><div class="payment-quote-item"><small>Rent due</small><strong data-rent-due>—</strong></div><div class="payment-quote-item"><small>Previous unpaid balance</small><strong data-previous-balance>—</strong></div></div><p class="form-hint">This payment applies only to the selected billing month. Select an earlier month to record a payment for a previous balance.</p></div><div class="payment-form-section"><h3>Payment received</h3><div class="form-grid"><label class="form-field">Amount received<input name="amountReceived" type="number" min="0.01" step="0.01" required></label><label class="form-field">Payment method<select name="paymentMethod" required><option>Cash</option><option>GCash</option><option>Maya</option><option>Bank Transfer</option><option>Other</option></select></label><label class="form-field" data-reference-field>Reference number<input name="referenceNumber" maxlength="100"></label><label class="form-field">Payment date<input name="paymentDate" type="date" value="${localDate()}" max="${localDate()}" required></label><label class="form-field full">Payment notes<textarea name="notes" maxlength="1000" placeholder="Optional details"></textarea></label><label class="form-check full" data-cash-check><input type="checkbox" name="confirmCashReceived"><span>Cash was physically received and verified. Confirm this payment now.</span></label></div></div><div class="payment-summary"><h3>Payment summary</h3><div><span>Remaining after this payment (if confirmed)</span><strong data-estimated-remaining>—</strong></div><div><span>Status</span><strong data-estimated-status>—</strong></div></div><p class="form-hint">GCash, Maya, and bank transfers remain pending until an administrator verifies the transaction. Pending payments are not counted as collected.</p><div data-quote-error class="form-error" hidden></div><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">Save payment</button></div></form>`, true);
            const form = modalContent.querySelector("[data-payment-form]");
            form.elements.paymentMethod.addEventListener("change", () => {
                const electronic = ["GCash", "Maya", "Bank Transfer"].includes(form.elements.paymentMethod.value);
                form.querySelector("[data-reference-field]").classList.toggle("required-field", electronic);
                form.elements.referenceNumber.required = electronic;
                form.querySelector("[data-cash-check]").hidden = form.elements.paymentMethod.value !== "Cash";
                updatePaymentEstimate(form);
            });
            form.elements.confirmCashReceived.addEventListener("change", () => updatePaymentEstimate(form));
            form.elements.amountReceived.addEventListener("input", () => updatePaymentEstimate(form));
            form.elements.tenantId.addEventListener("change", updatePaymentQuote);
            form.elements.billingMonth.addEventListener("change", updatePaymentQuote);
            await updatePaymentQuote();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function openTenantPaymentModal() {
        const month = localDate().slice(0, 7);
        try {
            const quote = await requestJson(`${paymentApi}?op=quote&month=${encodeURIComponent(month)}`);
            const available = Number(quote.availableToRecord);
            const hasBalance = Number.isFinite(available) && available > 0;
            const qrPanel = gcashQrUrl
                ? `<div class="gcash-payment-instructions" data-gcash-instructions hidden><div class="gcash-qr-frame"><img src="${esc(gcashQrUrl)}" alt="Scan this BAMStay GCash QR code" width="612" height="806"></div><div><strong>Scan to pay with GCash</strong><p>Open your GCash app and scan the QR. Complete the transfer, then enter the GCash transaction reference. Requests stay pending until verified by the property team.</p></div></div>`
                : `<div class="gcash-payment-unavailable" data-gcash-instructions hidden><strong>GCash QR not available</strong><p>The administrator has not added the payment QR yet. Please use Cash or contact the property team.</p></div>`;
            const paymentForm = `
                <form data-tenant-payment-form data-submission-id="${crypto.randomUUID()}">
                    <div class="tenant-payment-balance">
                        <div><small>Billing period</small><strong>${formatDate(quote.billingMonth)}</strong></div>
                        <div><small>Amount available</small><strong>${formatMoney(quote.availableToRecord)}</strong></div>
                        <div><small>Current rent balance</small><strong>${formatMoney(quote.remainingBalance)}</strong></div>
                    </div>
                    <div class="tenant-payment-layout">
                        <div class="tenant-payment-main">
                            <div class="form-grid">
                                <label class="form-field">Billing month<input name="billingMonth" type="month" value="${esc(month)}" max="${esc(month)}" min="${esc(quote.earliestBillingMonth || month)}" required></label>
                                <label class="form-field">Amount<input name="amount" type="number" min="0.01" max="${esc(quote.availableToRecord)}" step="0.01" value="${esc(quote.availableToRecord)}" required ${hasBalance ? "" : "disabled"}></label>
                                <label class="form-field">Payment method<select name="paymentMethod" required><option value="Cash">Cash</option>${gcashQrUrl ? '<option value="GCash">GCash</option>' : '<option value="GCash" disabled>GCash (QR not configured)</option>'}</select></label>
                                <label class="form-field" data-gcash-reference hidden>GCash transaction reference<input name="referenceNumber" maxlength="100" autocomplete="off"></label>
                                <label class="form-field">Payment date<input name="paymentDate" type="date" value="${localDate()}" max="${localDate()}" required></label>
                            </div>
                            <p class="tenant-payment-no-balance" data-no-balance ${hasBalance ? "hidden" : ""} role="status">There is no amount due for this billing month. Choose another month to review its balance.</p>
                            <p class="form-hint tenant-payment-note">Cash requests are verified by staff after you hand over payment. GCash requests are not receipts and will not count as paid until verified.</p>
                        </div>
                        <aside class="tenant-payment-sidebar">
                            <div class="gcash-payment-prompt" data-gcash-prompt><strong>Payment instructions</strong><p>Select GCash to display the official payment QR. For Cash, submit this request after handing payment to property staff.</p></div>
                            ${qrPanel}
                        </aside>
                    </div>
                    <div data-form-error class="form-error" hidden></div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-light" data-close>Cancel</button>
                        <button type="submit" class="btn btn-primary" ${hasBalance ? "" : "disabled"}>Submit payment request</button>
                    </div>
                </form>`;
            openModal("Add a rent payment", paymentForm, true);
            modal.querySelector(".modal-panel").classList.add("tenant-payment-modal");
            const form = modalContent.querySelector("[data-tenant-payment-form]");
            const method = form.elements.paymentMethod;
            const referenceField = form.querySelector("[data-gcash-reference]");
            const qrInstructions = form.querySelector("[data-gcash-instructions]");
            const qrPrompt = form.querySelector("[data-gcash-prompt]");
            const updateMethodFields = () => {
                const gcashSelected = method.value === "GCash";
                referenceField.hidden = !gcashSelected;
                form.elements.referenceNumber.required = gcashSelected;
                qrInstructions.hidden = !gcashSelected;
                qrPrompt.hidden = gcashSelected;
            };
            updateMethodFields();
            method.addEventListener("change", updateMethodFields);
            form.elements.billingMonth.addEventListener("change", async () => {
                const selectedMonth = form.elements.billingMonth.value;
                if (!selectedMonth) return;
                const amount = form.elements.amount;
                amount.disabled = true;
                try {
                    const updatedQuote = await requestJson(`${paymentApi}?op=quote&month=${encodeURIComponent(selectedMonth)}`);
                    amount.max = updatedQuote.availableToRecord;
                    amount.value = updatedQuote.availableToRecord;
                    amount.disabled = Number(updatedQuote.availableToRecord) <= 0;
                    form.querySelector(".tenant-payment-balance").innerHTML = `<div><small>Billing period</small><strong>${formatDate(updatedQuote.billingMonth)}</strong></div><div><small>Amount available</small><strong>${formatMoney(updatedQuote.availableToRecord)}</strong></div><div><small>Current rent balance</small><strong>${formatMoney(updatedQuote.remainingBalance)}</strong></div>`;
                    const updatedHasBalance = Number(updatedQuote.availableToRecord) > 0;
                    form.querySelector("[data-no-balance]").hidden = updatedHasBalance;
                    form.querySelector('[type="submit"]').disabled = !updatedHasBalance;
                } catch (error) {
                    showFormError(form, error);
                    form.querySelector('[type="submit"]').disabled = true;
                }
            });
        } catch (error) {
            showToast(error.message);
        }
    }

    async function reviewTenantPayment(submissionId, decision, reviewNote = "") {
        try {
            const result = await requestJson(paymentApi, {
                method: "POST",
                body: JSON.stringify({ action: "review-submission", submissionId, decision, reviewNote })
            });
            modal.hidden = true;
            showToast(decision === "accept" ? `Payment verified. Receipt ${result.receiptNumber} created.` : "Payment request declined.");
            await loadPayments();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function showReceipt(paymentId) {
        try {
            const result = await requestJson(`${paymentApi}?op=receipt&id=${encodeURIComponent(paymentId)}`);
            const item = result.receipt;
            openModal("Payment acknowledgment", `<article class="receipt-paper"><p class="eyebrow">BISTRO BAM · BAMSTAY</p><h2>Payment receipt</h2><p class="receipt-number">${esc(item.receiptNumber)}</p><div class="receipt-divider"></div><div class="detail-list"><div class="detail-item"><small>Tenant</small><strong>${esc(item.tenantName)}</strong></div><div class="detail-item"><small>Room / Bedspace</small><strong>${esc(item.roomId)} · ${esc(item.bedspaceId)}</strong></div><div class="detail-item"><small>Billing period</small><strong>${formatDate(item.billingMonth)}</strong></div><div class="detail-item"><small>Payment date</small><strong>${formatDate(item.paymentDate)}</strong></div><div class="detail-item"><small>Due date</small><strong>${formatDate(item.dueDate)}</strong></div><div class="detail-item"><small>Payment method</small><strong>${esc(item.paymentMethod)}</strong></div><div class="detail-item"><small>Reference number</small><strong>${esc(item.referenceNumber || "—")}</strong></div><div class="detail-item"><small>Rent due</small><strong>${formatMoney(item.rentDue)}</strong></div><div class="detail-item"><small>Late fee</small><strong>${formatMoney(item.lateFee)}</strong></div><div class="detail-item"><small>Amount received</small><strong>${formatMoney(item.amountReceived)}</strong></div><div class="detail-item"><small>Remaining balance</small><strong>${formatMoney(item.remainingBalance)}</strong></div></div><div class="receipt-status">${badge(item.status)}</div><p class="receipt-disclaimer">${item.status === "Paid" ? "This receipt acknowledges payment and the billed obligation is settled." : "This receipt acknowledges only the amount received. It does not indicate that the full rental obligation has been settled."}</p><small class="receipt-generated">Generated ${esc(new Date(item.createdAt.replace(" ", "T")).toLocaleString())}</small></article><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Close</button><button type="button" class="btn btn-primary" data-action="print">Print receipt</button></div>`, true);
            const details = modalContent.querySelector(".receipt-paper .detail-list");
            for (const [label, value] of [["Transaction no.", item.id], ["Processed by", item.processedBy]]) {
                const detail = document.createElement("div");
                const caption = document.createElement("small");
                const content = document.createElement("strong");
                detail.className = "detail-item";
                caption.textContent = label;
                content.textContent = value;
                detail.append(caption, content);
                details.append(detail);
            }
        } catch (error) {
            showToast(error.message);
        }
    }

    async function changePaymentStatus(action, paymentId, reason = "", onError = null) {
        try {
            await requestJson(paymentApi, { method: "POST", body: JSON.stringify({ action, paymentId, reason }) });
            modal.hidden = true;
            showToast(action === "confirm" ? "Payment verified and recorded as received." : action === "reverse" ? "Payment reversed with an audit record." : "Pending payment voided.");
            await loadPayments();
        } catch (error) {
            if (onError) onError(error);
            else showToast(error.message);
        }
    }

    function openPendingPaymentEdit(paymentId) {
        const record = paymentRecords.find((item) => item.id === paymentId && item.canEdit);
        if (!record) return showToast("Only a pending payment can be edited.");
        openModal("Edit pending payment", `<form data-edit-payment-form data-payment-id="${esc(record.id)}"><div class="form-grid"><label class="form-field">Tenant<input value="${esc(record.tenantName)}" disabled></label><label class="form-field">Billing month<input value="${esc(record.billingMonth.slice(0, 7))}" disabled></label><label class="form-field">Amount received<input name="amountReceived" type="number" min="0.01" step="0.01" value="${esc(record.amountReceived)}" required></label><label class="form-field">Payment method<select name="paymentMethod" required>${["Cash", "GCash", "Maya", "Bank Transfer", "Other"].map((item) => `<option ${item === record.paymentMethod ? "selected" : ""}>${item}</option>`).join("")}</select></label><label class="form-field">Reference number<input name="referenceNumber" maxlength="100" value="${esc(record.referenceNumber || "")}"></label><label class="form-field">Payment date<input name="paymentDate" type="date" value="${esc(record.paymentDate)}" max="${localDate()}" required></label><label class="form-field full">Notes<textarea name="notes" maxlength="1000"></textarea></label></div><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">Save changes</button></div></form>`, true);
    }

    function exportReport() {
        const from = content.querySelector("[data-report-from]").value;
        const to = content.querySelector("[data-report-to]").value;
        if (!reportData || reportData.from !== from || reportData.to !== to) {
            return showToast("Apply the current report range before exporting.");
        }
        const rows = [
            ["Month", "Expected", "Collected", "Pending", "Overdue", "Collection Rate"],
            ...reportData.monthly.map((item) => [
                item.month,
                item.expected,
                item.collected,
                item.pending,
                item.overdue,
                `${item.collectionRate}%`
            ]),
            ["Total", reportData.totals.expected, reportData.totals.collected, reportData.totals.pending, reportData.totals.overdue, `${reportData.totals.collectionRate}%`]
        ];
        const csv = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\r\n");
        const url = URL.createObjectURL(new Blob(["\uFEFF", csv], { type: "text/csv;charset=utf-8" }));
        const link = document.createElement("a");
        link.href = url;
        link.download = `bamstay-rent-report-${from}-to-${to}.csv`;
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function renderCleaning() {
        const rows = cleaningRows.map((row) => {
            const hasProof = row[5] !== "Assigned";
            const taskIcon = row[2] === "Kitchen" ? "⌂" : row[2].includes("CR") ? "◌" : "✧";
            const proof = hasProof
                ? `<button class="proof-button" type="button" data-action="photo-detail" aria-label="View proof photo for ${esc(row[0])}" title="View submitted proof"><span class="proof-icon">▧</span><span>View proof</span></button>`
                : `<span class="proof-pending"><span>◷</span> Awaiting upload</span>`;
            const action = row[5] === "Submitted"
                ? actionButton("✓ Verify task", "verify-cleaning", "btn-primary btn-sm")
                : actionButton("View details", "cleaning-detail");
            return `<tr><td><div class="cleaning-tenant">${avatar(row[0], true)}<span><strong>${esc(row[0])}</strong><small>Resident</small></span></div></td><td><span class="room-chip">${esc(row[1])}</span></td><td><div class="cleaning-task"><span class="task-type-icon">${taskIcon}</span><strong>${esc(row[2])}</strong></div></td><td><span class="cleaning-date">${esc(row[3])}</span></td><td><span class="cleaning-date">${esc(row[4])}</span></td><td>${proof}</td><td>${badge(row[5])}</td><td>${action}</td></tr>`;
        }).join("");
        if (role === "tenant") return `${pageTitle("Cleaning Schedule", "Your assigned tasks and submissions.")}<section class="cleaning-empty-state"><span class="cleaning-status-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m4 13 8-8 8 8M6.5 11v8h11v-8M9.5 19v-5h5v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div class="cleaning-empty-copy"><span class="cleaning-status-pill">Not configured</span><h2>Your cleaning schedule will appear here</h2><p>Assigned areas, due dates, and proof-submission instructions will be available once the cleaning schedule is set up for BAMStay.</p></div><button class="btn btn-light" type="button" data-page="home">Back to home</button></section>`;
        return `${pageTitle("Cleaning", "Assign shared-space tasks and verify submitted proof.", actionButton("＋ Assign task", "assign-cleaning", "btn-primary"))}${previewNote}<div class="grid columns-3" style="margin-bottom:15px">${stat("Assigned", "4", "✧", "Tasks in progress")}${stat("Submitted", "2", "◷", "Waiting for verification")}${stat("Verified", "12", "✓", "Completed this month")}</div><div class="toolbar"><div class="filter-group">${["All", "Assigned", "Submitted", "Verified"].map((item) => `<button class="filter-button ${activeFilter === item ? "active" : ""}" data-filter="${item}" type="button">${item}</button>`).join("")}</div><span class="assignment-count">${cleaningRows.length} sample assignments</span></div>${panel("Cleaning assignments", `<div class="table-wrap cleaning-table-wrap"><table class="cleaning-table"><thead><tr><th>Tenant</th><th>Room</th><th>Task</th><th>Assigned Date</th><th>Due Date</th><th>Proof Photo</th><th>Status</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>`, "Review task progress and verify resident submissions")}`;
    }

    function renderMaintenance() {
        if (role === "tenant") return `${pageTitle("Maintenance", "Report an issue and follow its status.")}<div class="empty-state">Maintenance requests are not connected to the database yet. No other resident’s maintenance records are displayed.</div>`;
        const cards = maintenanceRows.map((row) => `<article class="maintenance-card"><div class="card-top"><div><div class="card-meta">${initialsName(row[0])} <span>${esc(row[1])}</span> <span>Reported ${esc(row[4])}</span></div><h3 style="margin-top:8px">${esc(row[2])}</h3></div>${badge(row[5])}</div><p class="card-copy">${esc(row[3])}</p><div class="grid columns-3"><label class="form-field">Status<select class="inline-control" data-inline><option ${row[5] === "Pending" ? "selected" : ""}>Pending</option><option ${row[5] === "In-Progress" ? "selected" : ""}>In-Progress</option><option ${row[5] === "Resolved" ? "selected" : ""}>Resolved</option></select></label><label class="form-field">Assigned staff<input class="inline-control" value="${row[5] === "Pending" ? "" : "Property Staff"}" placeholder="Assign staff" data-inline></label>${row[5] === "Resolved" ? '<label class="form-field">Resolution<input class="inline-control" value="Repaired and tested" data-inline></label>' : '<div class="form-field"><span>Photo</span><button class="btn btn-light btn-sm" data-action="photo-detail" type="button">View photo</button></div>'}</div></article>`).join("");
        return `${pageTitle("Maintenance", role === "admin" ? "Track property issues and coordinate repairs." : "Report an issue and follow your requests.", role === "tenant" ? actionButton("＋ Report issue", "report-issue", "btn-primary") : "")}${previewNote}<div class="toolbar"><div class="filter-group">${["All", "Pending", "In-Progress", "Resolved"].map((item) => `<button class="filter-button ${activeFilter === item ? "active" : ""}" data-filter="${item}" type="button">${item}</button>`).join("")}</div></div><div class="maintenance-list">${cards}</div>`;
    }

    function renderAnnouncements() {
        const cards = announcements.map(([title, type, text, date, author]) => `<article class="announcement-card ${type}"><div class="card-top"><div><h3>${esc(title)}</h3><div class="card-meta" style="margin-top:6px">${badge(type[0].toUpperCase() + type.slice(1))} ${badge("Active")}</div></div>${role === "admin" ? `<div class="card-actions">${actionButton("Edit", "edit-announcement")}${actionButton("Hide", "toggle-announcement")}${actionButton("Delete", "delete-item", "btn-danger btn-sm")}</div>` : ""}</div><p class="card-copy">${esc(text)}</p><div class="card-meta"><span>${esc(date)}</span><span>Posted by ${esc(author)}</span></div></article>`).join("");
        return `${pageTitle("Announcements", role === "admin" ? "Keep residents informed with timely updates." : "Updates and notices from the BAMStay team.", role === "admin" ? actionButton("＋ New announcement", "add-announcement", "btn-primary") : "")}${previewNote}<div class="announcement-list">${cards}</div>`;
    }

    function renderRules() {
        const cards = rules.map(([title, text], index) => `<article class="rule-card"><span class="rule-number">${String(index + 1).padStart(2, "0")}</span><div class="rule-content"><div class="card-top"><h3>${esc(title)}</h3><div class="card-actions">${badge("Active")}${role === "admin" ? `${actionButton("Edit", "edit-rule")}${actionButton("Hide", "toggle-rule")}${actionButton("Delete", "delete-item", "btn-danger btn-sm")}` : ""}</div></div><p class="card-copy">${esc(text)}</p></div></article>`).join("");
        return `${pageTitle("House Rules", role === "admin" ? "Set clear expectations for a welcoming shared home." : "A few shared agreements help make BAMStay feel like home.", role === "admin" ? actionButton("＋ Add rule", "add-rule", "btn-primary") : "")}${previewNote}<div class="rule-list">${cards}</div>`;
    }

    function renderInquiries() {
        const cards = inquiries.map((row) => `<article class="inquiry-card"><div class="card-top"><div><h3>${esc(row[0])}</h3><div class="card-meta" style="margin-top:5px"><span>${esc(row[1])}</span><span>Submitted ${esc(row[2])}</span></div></div><select class="inline-control" data-inline><option ${row[5] === "Pending" ? "selected" : ""}>Pending</option><option ${row[5] === "Responded" ? "selected" : ""}>Responded</option><option ${row[5] === "Declined" ? "selected" : ""}>Declined</option></select></div><p class="card-meta" style="margin-top:10px">Room interest: ${esc(row[3])}</p><p class="card-copy">${esc(row[4])}</p><div class="card-actions">${actionButton("Reply", "reply-inquiry", "btn-primary btn-sm")} ${actionButton("View details", "inquiry-detail")}</div></article>`).join("");
        return `${pageTitle("Inquiries", "Follow up with prospective residents and answer their questions.")}${previewNote}<div class="toolbar"><div class="filter-group">${["All", "Pending", "Responded", "Declined"].map((item) => `<button class="filter-button ${activeFilter === item ? "active" : ""}" data-filter="${item}" type="button">${item}</button>`).join("")}</div></div><div class="inquiry-list">${cards}</div>`;
    }

    function renderApplications() {
        const rows = applications.map((row) => `<tr><td>${initialsName(row[0])}<small style="display:block;margin-left:34px;color:#a09799">${esc(row[1])}</small></td><td>${esc(row[2])}</td><td>${esc(row[3])}</td><td>${esc(row[4])}</td><td><button class="btn btn-light btn-sm" data-action="id-preview">View ID</button></td><td>${row[5].includes("%") ? `<span class="status passed">✓ ${esc(row[5])}</span>` : badge(row[5])}</td><td>${badge(row[6])}</td><td><button class="icon-action" data-action="application-detail" aria-label="View application">⌕</button>${row[6] === "Pending Review" ? ' <button class="icon-action" data-action="approve-application" aria-label="Approve">✓</button> <button class="icon-action" data-action="reject-application" aria-label="Reject">×</button>' : ""}${row[6] === "Ready to Activate" ? ' <button class="btn btn-primary btn-sm" data-action="activate-account">Activate</button>' : ""}</td></tr>`).join("");
        return `${pageTitle("Applications", "Review applicant details and identity verification.", actionButton("ⓘ Review guide", "application-guide", "btn-light"))}${previewNote}<div class="grid stats-grid application-stats-grid">${stat("Pending Review", "4", "◷", "Require a decision")}${stat("Awaiting Verification", "2", "♧", "Face capture needed")}${stat("Ready to Activate", "1", "✓", "Verified applicants")}${stat("Accounts Created", "11", "♙", "Active tenant accounts")}</div>${panel("Applicant queue", `<div class="table-wrap"><table><thead><tr><th>Applicant</th><th>Contact</th><th>Room Preference</th><th>Submitted</th><th>ID Upload</th><th>Face Verification</th><th>Status</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table></div>`)}`;
    }

    function renderFeedback() {
        const cards = reviews.map(([name, date, rating, comment]) => `<article class="review-card"><div class="card-top"><div class="tenant-cell">${avatar(name, true)}<span><strong>${esc(name)}</strong><small style="display:block;color:#a09799">${esc(date)}</small></span></div><span class="review-rating">${"★".repeat(rating)}${"☆".repeat(5 - rating)}</span></div><p class="review-comment">“${esc(comment)}”</p></article>`).join("");
        const left = role === "admin" ? `<div class="grid columns-3" style="margin-bottom:15px">${stat("Average Rating", "4.8 ★", "☆", "Across all reviews")}${stat("Total Reviews", "12", "▤", "Resident feedback")}${stat("Positive Reviews", "10", "♡", "4–5 star ratings")}</div>` : "";
        const form = role === "tenant" ? `<section class="panel"><div class="panel-head"><div><h2>Leave a review</h2><p>Share your experience with the BAMStay community.</p></div></div><div class="panel-body"><p class="page-subtitle" id="rating-label">Choose a rating</p><div class="stars-input">${[1, 2, 3, 4, 5].map((num) => `<button class="star-button" type="button" data-star="${num}" aria-label="${num} stars">★</button>`).join("")}</div><label class="form-field">Your comment<textarea placeholder="Tell us what you enjoy about living here..."></textarea></label><div class="modal-actions"><button class="btn btn-primary" type="button" data-action="submit-feedback">Submit feedback</button></div></div></section>` : "";
        const tenantReviews = `<div class="review-list">${reviews.slice(0, 2).map(([, date, rating, comment]) => `<article class="review-card"><div class="card-top"><strong>${esc(date)}</strong><span class="review-rating">${"★".repeat(rating)}${"☆".repeat(5 - rating)}</span></div><p class="review-comment">“${esc(comment)}”</p></article>`).join("")}</div>`;
        return `${pageTitle("Feedback", role === "admin" ? "Listen to resident experiences and celebrate what is working." : "Your voice helps make BAMStay feel even more like home.")}${previewNote}${left}<div class="grid ${role === "tenant" ? "columns-even" : ""}">${form}${panel(role === "tenant" ? "Your previous reviews" : "Recent reviews", role === "tenant" ? tenantReviews : `<div class="review-list">${cards}</div>`)}</div>`;
    }

    function renderPaymentDonut(segments = [], totalCollected = 0, expectedTotal = 0) {
        const total = segments.reduce((sum, segment) => sum + segment.value, 0);
        const circumference = 2 * Math.PI * 48;
        let offset = 0;
        const arcs = segments.map((segment, index) => {
            const length = total > 0 ? circumference * segment.value / total : 0;
            const arc = `<circle class="donut-segment" style="--segment-dasharray:${length} ${circumference - length};--segment-delay:${index * 0.12}s" cx="60" cy="60" r="48" fill="none" stroke="${segment.color}" stroke-width="12" stroke-dasharray="${length} ${circumference - length}" stroke-dashoffset="${-offset}"><title>${segment.label}: ${segment.value}</title></circle>`;
            offset += length;
            return arc;
        }).join("");
        const legend = segments.map((segment) => `<div class="payment-legend-row"><span><i class="legend-swatch" style="--swatch:${segment.color}"></i>${esc(segment.label)}</span><strong>${segment.value}</strong><small>${(total > 0 ? segment.value / total * 100 : 0).toFixed(1)}%</small></div>`).join("");
        const collectionRate = expectedTotal > 0 ? (totalCollected / expectedTotal * 100).toFixed(1) : "0.0";
        const label = segments.map((segment) => `${segment.value} ${segment.label.toLowerCase()}`).join(", ");
        return `<div class="donut-layout"><div class="donut-visual"><svg viewBox="0 0 120 120" role="img" aria-label="Payment obligations: ${esc(label || "no obligations")}"><circle cx="60" cy="60" r="48" fill="none" stroke="#f1eaec" stroke-width="12"></circle><g transform="rotate(-90 60 60)">${arcs}</g><text x="60" y="58" text-anchor="middle" class="donut-value">${collectionRate}%</text><text x="60" y="73" text-anchor="middle" class="donut-caption">COLLECTED</text></svg></div><div class="payment-legend"><div class="payment-legend-heading"><span>STATUS</span><span>COUNT</span><span>SHARE</span></div>${legend || `<p class="empty-state">No obligations in this period.</p>`}<div class="payment-period-total"><span>Confirmed collected</span><strong>${formatMoney((totalCollected / 100).toFixed(2))}</strong></div></div></div>`;
    }

    function renderMaintenanceChart() {
        return `<div class="empty-state">Maintenance request records are not connected to a database yet, so no period totals can be shown.</div>`;
    }

    function renderCollectionChart(series = []) {
        if (series.length === 0) return `<div class="empty-state">No rental obligations exist in this date range.</div>`;
        const months = series.map((item) => formatDate(item.month).replace(/ \d{4}$/, ""));
        const expected = series.map((item) => Number(item.expected));
        const collected = series.map((item) => Number(item.collected));
        const plot = { left: 70, right: 870, top: 38, bottom: 285 };
        const maxValue = Math.max(100, Math.ceil(Math.max(...expected, ...collected) / 10000) * 10000);
        const plotHeight = plot.bottom - plot.top;
        const xAt = (index) => plot.left + index * (plot.right - plot.left) / Math.max(months.length - 1, 1);
        const yAt = (value) => plot.bottom - value / maxValue * plotHeight;
        const curvePath = (points) => points.slice(0, -1).reduce((path, point, index) => {
            const previous = points[index - 1] || point;
            const next = points[index + 1];
            const following = points[index + 2] || next;
            const controlOneX = point.x + (next.x - previous.x) / 6;
            const controlOneY = point.y + (next.y - previous.y) / 6;
            const controlTwoX = next.x - (following.x - point.x) / 6;
            const controlTwoY = next.y - (following.y - point.y) / 6;
            return `${path} C ${controlOneX} ${controlOneY}, ${controlTwoX} ${controlTwoY}, ${next.x} ${next.y}`;
        }, `M ${points[0].x} ${points[0].y}`);
        const lines = [0, 1, 2, 3, 4].map((step) => {
            const value = maxValue * step / 4;
            const y = yAt(value);
            return `<g><line x1="${plot.left}" y1="${y}" x2="${plot.right}" y2="${y}" class="chart-gridline"></line><text x="${plot.left - 12}" y="${y + 4}" text-anchor="end" class="chart-axis-label">${value === 0 ? "₱0" : `₱${(value / 1000).toFixed(value % 1000 ? 1 : 0)}k`}</text></g>`;
        }).join("");
        const collectedPoints = collected.map((value, index) => ({ x: xAt(index), y: yAt(value) }));
        const expectedPoints = expected.map((value, index) => ({ x: xAt(index), y: yAt(value) }));
        const collectedLine = curvePath(collectedPoints);
        const expectedLine = curvePath(expectedPoints);
        const areaPath = `${collectedLine} L ${plot.right} ${plot.bottom} L ${plot.left} ${plot.bottom} Z`;
        const labels = months.map((month, index) => `<text x="${xAt(index)}" y="${plot.bottom + 25}" text-anchor="middle" class="chart-month-label">${month}</text>`).join("");
        const points = collectedPoints.map((point, index) => `<circle cx="${point.x}" cy="${point.y}" r="4" class="chart-data-point"><title>${esc(series[index].month)} · ${formatMoney(series[index].collected)} collected</title></circle>`).join("");
        return `<div class="collection-chart-wrap"><svg class="collection-chart" viewBox="0 0 900 345" role="img" aria-label="Monthly rent collected compared with expected for selected months"><defs><linearGradient id="collection-area-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#762a42" stop-opacity=".28"></stop><stop offset="100%" stop-color="#762a42" stop-opacity=".025"></stop></linearGradient></defs>${lines}<text x="${plot.left}" y="20" class="chart-axis-title">RENT COLLECTION · PHP</text><path d="${areaPath}" class="chart-area-fill"></path><path d="${expectedLine}" pathLength="1" class="chart-expected-line"></path><path d="${collectedLine}" pathLength="1" class="chart-collected-line"></path>${points}${labels}</svg></div><div class="chart-legend chart-legend-center"><span><i class="legend-dot"></i>Collected</span><span><i class="legend-dot pale"></i>Expected</span></div>`;
    }

    let reportData = null;

    function renderReports() {
        const toDate = new Date();
        const fromDate = new Date(toDate.getFullYear(), toDate.getMonth() - 5, 1);
        const monthValue = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
        const kpis = `<div class="grid reports-kpis"><article class="report-kpi"><span class="report-kpi-icon wine">₱</span><div><small>Total Expected Rent</small><strong data-kpi="expected">—</strong><span>For selected period</span></div></article><article class="report-kpi"><span class="report-kpi-icon wine">✓</span><div><small>Total Collected</small><strong data-kpi="collected">—</strong><span data-kpi="collection-rate">Confirmed payments only</span></div></article><article class="report-kpi"><span class="report-kpi-icon wine">◷</span><div><small>Outstanding Balance</small><strong data-kpi="outstanding">—</strong><span>Across recorded obligations</span></div></article><article class="report-kpi"><span class="report-kpi-icon wine">▣</span><div><small>Overdue Amount</small><strong data-kpi="overdue">—</strong><span data-kpi="overdue-tenants">Server-calculated</span></div></article><article class="report-kpi"><span class="report-kpi-icon wine">⌂</span><div><small>Occupancy Rate</small><strong data-kpi="occupancy">—</strong><span data-kpi="occupancy-count">Active tenant accounts</span></div></article></div>`;
        const table = `<div class="table-wrap report-table-wrap"><table class="report-table"><thead><tr><th>Month</th><th>Expected</th><th>Collected</th><th>Pending</th><th>Late / Unpaid</th><th>Collection Rate</th></tr></thead><tbody data-report-rows><tr><td colspan="6">Loading rental obligations…</td></tr></tbody></table></div>`;
        const attention = `<div class="report-side-stack"><section class="panel report-insights"><div class="report-side-heading"><span class="report-side-icon">♧</span><h2>Quick insights</h2></div><div data-report-insights class="empty-state">Loading payment insights…</div></section><section class="panel report-attention"><div class="report-side-heading"><span class="attention-mark">!</span><h2>Need attention</h2></div><div data-report-attention class="empty-state">Loading obligations…</div></section></div>`;
        const contentHtml = `${pageTitle("Reports", "A clear view of rent collection and property operations.")}<div class="toolbar report-toolbar"><div class="filter-group"><label class="form-field">From month<input type="month" data-report-from value="${monthValue(fromDate)}"></label><label class="form-field">To month<input type="month" data-report-to value="${monthValue(toDate)}"></label><button class="btn btn-primary btn-sm" type="button" data-action="apply-report">Apply</button>${actionButton("↓ Export report", "export-report", "btn-light")}</div></div><div data-report-error class="form-error" hidden></div>${kpis}<div class="grid reports-row reports-primary-row">${panel("Monthly Collection Trend", `<div data-report-trend>${renderCollectionChart()}</div>`, "Confirmed receipts compared with expected rent", "", "report-trend-panel")}${panel("Payment Completion", `<div data-report-donut>${renderPaymentDonut()}</div>`, "Obligations in the selected period", "", "report-donut-panel")}</div><div class="grid reports-lower-row">${panel("Maintenance This Period", renderMaintenanceChart(), "No maintenance data source is configured", "", "report-maintenance-panel")}${panel("Monthly Rental Summary", table, "Pending and overdue amounts are outstanding balances categorized by due date.", "", "report-table-panel")}${attention}</div>`;
        queueMicrotask(() => loadReports());
        return contentHtml;
    }

    function reportMonths(from, to, items) {
        const byMonth = new Map(items.map((item) => [item.month.slice(0, 7), item]));
        const result = [];
        const start = new Date(`${from}-01T00:00:00`);
        const end = new Date(`${to}-01T00:00:00`);
        for (const cursor = new Date(start); cursor <= end; cursor.setMonth(cursor.getMonth() + 1)) {
            const key = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, "0")}`;
            result.push(byMonth.get(key) || { month: `${key}-01`, expected: "0.00", collected: "0.00", pending: "0.00", overdue: "0.00", collectionRate: 0 });
        }
        return result;
    }

    async function loadReports() {
        if (role !== "admin" || activePage !== "reports") return;
        const requestId = ++reportRequestId;
        reportData = null;
        const errorBox = content.querySelector("[data-report-error]");
        errorBox.hidden = true;
        const from = content.querySelector("[data-report-from]").value;
        const to = content.querySelector("[data-report-to]").value;
        if (!from || !to || from > to) {
            errorBox.textContent = "Choose a valid date range with the start month no later than the end month.";
            errorBox.hidden = false;
            return;
        }
        try {
            await requestJson(paymentApi, { method: "POST", body: JSON.stringify({ action: "initialize" }) });
            const result = await requestJson(`${paymentApi}?op=report&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
            if (requestId !== reportRequestId || activePage !== "reports") return;
            result.monthly = reportMonths(from, to, result.monthly);
            reportData = { ...result, from, to };
            content.querySelector("[data-report-rows]").innerHTML = `${result.monthly.map((item) => `<tr><td><strong>${formatDate(item.month)}</strong></td><td>${formatMoney(item.expected)}</td><td>${formatMoney(item.collected)}</td><td>${formatMoney(item.pending)}</td><td>${formatMoney(item.overdue)}</td><td><div class="collection-rate-cell"><div class="progress-track"><div class="progress-fill" style="width:${Math.max(0, Math.min(100, item.collectionRate))}%"></div></div><strong>${item.collectionRate}%</strong></div></td></tr>`).join("")}<tr><td><strong>Total</strong></td><td><strong>${formatMoney(result.totals.expected)}</strong></td><td><strong>${formatMoney(result.totals.collected)}</strong></td><td><strong>${formatMoney(result.totals.pending)}</strong></td><td><strong>${formatMoney(result.totals.overdue)}</strong></td><td><strong>${result.totals.collectionRate}%</strong></td></tr>`;
            content.querySelector('[data-kpi="expected"]').textContent = formatMoney(result.totals.expected);
            content.querySelector('[data-kpi="collected"]').textContent = formatMoney(result.totals.collected);
            content.querySelector('[data-kpi="collection-rate"]').textContent = `${result.totals.collectionRate}% of expected rent`;
            content.querySelector('[data-kpi="outstanding"]').textContent = formatMoney(result.summary.outstandingBalance);
            content.querySelector('[data-kpi="overdue"]').textContent = formatMoney(result.summary.overdueAmount);
            content.querySelector('[data-kpi="overdue-tenants"]').textContent = `${result.summary.overdueTenantCount} tenant${result.summary.overdueTenantCount === 1 ? "" : "s"} overdue`;
            content.querySelector('[data-kpi="occupancy"]').textContent = `${result.occupancy.rate}%`;
            content.querySelector('[data-kpi="occupancy-count"]').textContent = `${result.occupancy.activeTenants} of ${result.occupancy.totalBeds} beds assigned`;
            const segments = [
                { label: "Paid", value: result.obligations.paid, color: "#762a42" },
                { label: "Pending", value: result.obligations.pending, color: "#d8bac4" },
                { label: "Partially paid", value: result.obligations.partial, color: "#a76078" },
                { label: "Overdue", value: result.obligations.overdue, color: "#d49a3a" }
            ];
            content.querySelector("[data-report-donut]").innerHTML = renderPaymentDonut(segments, Number(result.totals.collected) * 100, Number(result.totals.expected) * 100);
            content.querySelector("[data-report-trend]").innerHTML = renderCollectionChart(result.monthly);
            const insights = [];
            if (result.summary.overdueTenantCount > 0) insights.push(`<div class="insight-row"><span class="insight-icon alert">!</span><p><strong>${result.summary.overdueTenantCount} tenants have overdue balances</strong><small>${formatMoney(result.summary.overdueAmount)} currently overdue</small></p></div>`);
            if (result.summary.pendingCount > 0) insights.push(`<div class="insight-row"><span class="insight-icon calendar">◷</span><p><strong>${result.summary.pendingCount} payment${result.summary.pendingCount === 1 ? "" : "s"} await verification</strong><small>Pending payments are excluded from collections</small></p></div>`);
            if (insights.length === 0) insights.push(`<div class="empty-state">No overdue balances or pending confirmations were found.</div>`);
            content.querySelector("[data-report-insights]").innerHTML = insights.join("");
            const attentionRows = [];
            if (result.summary.overdueTenantCount > 0) attentionRows.push(`<button type="button" class="attention-link" data-page="payments" data-payment-status="Overdue"><span>₱</span><span>Overdue rent (${result.summary.overdueTenantCount} tenants)</span><b>›</b></button>`);
            if (result.summary.pendingCount > 0) attentionRows.push(`<button type="button" class="attention-link" data-page="payments"><span>◷</span><span>Pending payment verification (${result.summary.pendingCount})</span><b>›</b></button>`);
            if (attentionRows.length === 0) attentionRows.push(`<div class="empty-state">Nothing needs attention for rent payments.</div>`);
            content.querySelector("[data-report-attention]").innerHTML = attentionRows.join("");
        } catch (error) {
            if (requestId !== reportRequestId || activePage !== "reports") return;
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        }
    }

    function renderTenantHome() {
        queueMicrotask(() => loadTenantPortal());
        const announcementsState = `<div class="tenant-module-state"><span class="tenant-module-icon" aria-hidden="true">i</span><div><strong>Announcements will appear here</strong><p>There are no live announcement records connected to your portal yet.</p></div></div>`;
        const rulesState = `<div class="tenant-module-state"><span class="tenant-module-icon" aria-hidden="true">✓</span><div><strong>House rules will appear here</strong><p>Published property guidelines will be available here once configured.</p></div></div>`;
        return `${pageTitle("Welcome home", "Your resident information and payment status.")}<section class="cleaning-overview-card" aria-labelledby="home-cleaning-title"><span class="cleaning-overview-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m4 13 8-8 8 8M6.5 11v8h11v-8M9.5 19v-5h5v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="m17.5 4 .5-1.5.5 1.5 1.5.5-1.5.5-.5 1.5-.5-1.5-1.5-.5 1.5-.5Z" fill="currentColor"/></svg></span><div class="cleaning-overview-copy"><p class="eyebrow">CLEANING SCHEDULE</p><h2 id="home-cleaning-title">Your cleaning assignments</h2><p>Assigned areas, due dates, and proof-submission instructions will appear here once the schedule is set up.</p></div><div class="cleaning-overview-actions"><span class="cleaning-status-pill">Not configured</span><button class="btn btn-light btn-sm" type="button" data-page="cleaning">View schedule <span aria-hidden="true">→</span></button></div></section><div class="grid stats-grid tenant-home-stats">${stat("Room", "Loading…", "⌂", "Assigned room")}${stat("Bedspace", "Loading…", "▦", "Assigned bed")}${stat("Latest Payment", "Loading…", "✓", "Confirmed payment record")}${stat("Move-in Date", "Loading…", "♡", "Resident record")}</div><div data-tenant-profile-error class="form-error" hidden></div><div class="grid columns-even tenant-home-sections">${panel("Recent announcements", announcementsState, "Updates from your property team.", '<a class="text-link" href="#" data-page="announcements">View announcements →</a>', "tenant-home-panel")}${panel("House rules", rulesState, "Guidelines for a safe, comfortable home.", '<a class="text-link" href="#" data-page="rules">View house rules →</a>', "tenant-home-panel")}</div>`;
    }

    function renderMyRoom() {
        queueMicrotask(() => loadTenantPortal());
        return `${pageTitle("My Room", "Your assigned room, bedspace, and active roommates.")}<div data-tenant-profile-error class="form-error" hidden></div><div data-myroom-content class="empty-state">Loading your room information…</div>`;
    }

    function renderHome() { return role === "tenant" ? renderTenantHome() : renderOverview(); }

    function renderPage() {
        renderNav();
        const pages = {
            overview: renderOverview, home: renderHome, rooms: renderRooms, tenants: renderTenants,
            payments: renderPayments, cleaning: renderCleaning, maintenance: renderMaintenance,
            announcements: renderAnnouncements, rules: renderRules, inquiries: renderInquiries,
            applications: renderApplications, feedback: renderFeedback, reports: renderReports,
            myroom: renderMyRoom
        };
        activeFilter = "All";
        content.innerHTML = (pages[activePage] || renderHome)();
        content.classList.toggle("reports-page", activePage === "reports");
        content.classList.remove("page-enter");
        void content.offsetWidth;
        content.classList.add("page-enter");
        if (["rooms", "overview"].includes(activePage) && role === "admin") queueMicrotask(() => loadRooms());
        document.getElementById("sidebar").classList.remove("open");
    }

    function showToast(message) {
        toast.textContent = message;
        toast.classList.add("show");
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toast.classList.remove("show"), 2800);
    }

    function openModal(title, body, wide = false) {
        modalContent.innerHTML = `<p class="eyebrow">BAMSTAY PORTAL</p><h2 id="modal-title">${title}</h2><p class="modal-intro">Review the details below.</p>${body}`;
        modal.querySelector(".modal-panel").classList.toggle("wide", wide);
        modal.hidden = false;
        modal.querySelector(".modal-close").focus();
    }

    function formModal(title, fields) {
        const controls = fields.map(([label, type = "text", placeholder = ""]) => {
            if (type === "textarea") return `<label class="form-field full">${label}<textarea placeholder="${placeholder}"></textarea></label>`;
            if (type === "select") {
                let options = ["Active", "Inactive"];
                if (label === "Select tenant") options = tenants.map((tenant) => tenant.name);
                if (label === "Assign room") options = rooms.map((room) => room.name);
                if (label === "Assign bedspace") options = rooms.find((room) => room.name === "Room 2").beds.map((bed) => bed.label);
                if (label === "Payment type") options = ["Cash", "GCash", "Bank Transfer"];
                if (label === "Type") options = ["Info", "Warning", "Success"];
                if (label === "Status" && title.toLowerCase().includes("payment")) options = ["Paid", "Pending", "Overdue"];
                return `<label class="form-field">${label}<select><option value="">Select ${label.toLowerCase()}</option>${options.map((option) => `<option>${esc(option)}</option>`).join("")}</select></label>`;
            }
            if (type === "file") return `<label class="form-field">${label}<input type="file" accept="image/*"></label>`;
            const value = label === "Amount" ? ' value="1800"' : "";
            return `<label class="form-field">${label}<input type="${type}" placeholder="${placeholder}"${value}></label>`;
        }).join("");
        openModal(title, `<form data-demo-form><div class="form-grid">${controls}</div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">Save changes</button></div></form>`);
    }

    function roomDetailModal(roomId) {
        const room = rooms.find((item) => item.id === roomId) || rooms[0];
        const beds = room.beds.map((bed) => {
            const occupied = bed.status === "occupied";
            const statusLabel = occupied ? "Occupied" : bed.status === "maintenance" ? "Maintenance" : "Available";
            return `<div class="bed-tile">${bed.image ? `<img class="bedspace-photo" src="${esc(bed.image)}" alt="${esc(bed.label)}" loading="lazy">` : ""}<strong>${esc(bed.label)}</strong><small>${esc(bed.tenantName || statusLabel)}</small>${badge(statusLabel)}<div class="room-bed-actions"><button class="btn btn-light btn-sm" type="button" data-action="edit-bedspace" data-room="${esc(room.id)}" data-bedspace="${esc(bed.id)}">Edit</button>${bed.hasTenantRecord ? "" : `<button class="btn btn-light btn-sm" type="button" data-action="delete-bedspace" data-bedspace="${esc(bed.id)}">Remove</button>`}</div></div>`;
        }).join("");
        const imageActions = `<div class="card-actions"><button class="btn btn-light btn-sm" type="button" data-action="change-room-photo" data-room="${esc(room.id)}">Change photo</button><button class="btn btn-light btn-sm" type="button" data-action="edit-room" data-room="${esc(room.id)}">Edit room details</button></div>`;
        openModal(room.name, `<img src="${esc(room.image)}" alt="${esc(room.name)}" class="room-detail-image"><div class="room-detail-actions">${imageActions}</div><p class="card-copy">${esc(room.description)}</p><div class="feature-tags">${room.features.map((feature) => `<span class="feature-tag">${esc(feature)}</span>`).join("")}</div><div class="panel-head room-bedspace-heading"><div><h2>Bedspaces</h2><p>${room.beds.length} bedspaces · ${room.counts.occupied} occupied</p></div><button class="btn btn-light btn-sm" type="button" data-action="add-bedspace" data-room="${esc(room.id)}">＋ Add bedspace</button></div><div class="room-layout">${beds || '<div class="empty-state">No bedspaces added yet. Add a bedspace to make this room available for tenant assignment.</div>'}</div>`, true);
    }

    function roomFormModal(roomId = "") {
        const room = roomId ? rooms.find((item) => item.id === roomId) : null;
        if (roomId && !room) {
            showToast("That room could not be found.");
            return;
        }
        openModal(room ? "Edit room details" : "Add room", `
            <form data-room-form data-room-id="${esc(room?.id || "")}">
                <div class="form-grid">
                    <label class="form-field">Room number<input name="roomNumber" maxlength="60" value="${esc(room?.room_number || "")}" placeholder="e.g. Room 6" required></label>
                    <label class="form-field">Room name<input name="name" maxlength="100" value="${esc(room?.name || "")}" placeholder="e.g. Garden Room" required></label>
                    <label class="form-field full">Description<textarea name="description" maxlength="2000" placeholder="Describe the room">${esc(room?.description || "")}</textarea></label>
                    <label class="form-field full">Features <small>Separate features with commas</small><input name="features" maxlength="1200" value="${esc((room?.features || []).join(", "))}" placeholder="Ceiling Fan, Study Table"></label>
                    <label class="form-field">Monthly rent (₱)<input name="monthlyRate" type="number" min="0.01" step="0.01" value="${esc(room?.monthlyRate || "1800.00")}" required><small>Used as the default for new tenant assignments; current tenants’ rent is unchanged.</small></label>
                    <label class="form-field">Status<select name="status" required><option value="active" ${!room || room.status === "active" ? "selected" : ""}>Active</option><option value="inactive" ${room?.status === "inactive" ? "selected" : ""}>Inactive</option></select></label>
                    <div class="form-field full catalog-photo-field"><span>Room photo <small>(optional)</small></span><div class="room-photo-preview"><img src="${esc(room?.image || "")}" alt="${esc(room?.name || "New room")} photo preview" data-catalog-photo-preview ${room?.image ? "" : "hidden"}><span data-photo-placeholder ${room?.image ? "hidden" : ""}>Choose a room photo to preview</span></div><label class="room-photo-picker"><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-catalog-photo-file><small>JPG, PNG, or WebP · maximum 5 MB</small></label></div>
                </div>
                <div data-form-error class="form-error" hidden></div>
                <div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">${room ? "Save room" : "Add room"}</button></div>
            </form>`, true);
    }

    function bedspaceFormModal(roomId, bedspaceId = "") {
        const room = rooms.find((item) => item.id === roomId);
        const bed = room?.beds.find((item) => item.id === bedspaceId);
        if (!room || (bedspaceId && !bed)) {
            showToast("That room or bedspace could not be found.");
            return;
        }
        const occupied = bed?.status === "occupied";
        openModal(bed ? "Edit bedspace" : "Add bedspace", `
            <form data-bedspace-form data-room-id="${esc(room.id)}" data-bedspace-id="${esc(bed?.id || "")}">
                <p class="card-copy">${esc(room.room_number)} · ${esc(room.name)}</p>
                <div class="form-grid">
                    <label class="form-field">Bedspace label<input name="label" maxlength="60" value="${esc(bed?.label || "")}" placeholder="e.g. Bed I" required></label>
                    <label class="form-field">Status<select name="status" ${occupied ? "disabled" : ""} required><option value="available" ${!bed || bed.status === "available" || occupied ? "selected" : ""}>Available</option><option value="maintenance" ${bed?.status === "maintenance" ? "selected" : ""}>Maintenance</option></select>${occupied ? '<small>Occupied status is managed by the tenant assignment.</small>' : ""}</label>
                    <div class="form-field full catalog-photo-field"><span>Bedspace photo <small>(optional)</small></span><div class="room-photo-preview bedspace-photo-preview"><img src="${esc(bed?.image || room.image)}" alt="${esc(bed?.label || "New bedspace")} photo preview" data-catalog-photo-preview><span data-photo-placeholder hidden>Choose a bedspace photo to preview</span></div><label class="room-photo-picker"><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-catalog-photo-file><small>JPG, PNG, or WebP · maximum 5 MB. Without a photo, the room photo is used.</small></label></div>
                </div>
                <div data-form-error class="form-error" hidden></div>
                <div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary">${bed ? "Save bedspace" : "Add bedspace"}</button></div>
            </form>`);
    }

    function applyRoomList(result) {
        rooms = result.rooms;
        if (activePage === "rooms") content.innerHTML = renderRooms();
    }

    async function saveRoomForm(form) {
        const isNewRoom = !form.dataset.roomId;
        const result = await requestJson(roomsApi, {
            method: "POST",
            body: JSON.stringify({
                action: "save-room",
                id: form.dataset.roomId,
                roomNumber: form.elements.roomNumber.value.trim(),
                name: form.elements.name.value.trim(),
                description: form.elements.description.value.trim(),
                features: form.elements.features.value.split(",").map((feature) => feature.trim()).filter(Boolean),
                monthlyRate: form.elements.monthlyRate.value,
                status: form.elements.status.value
            })
        });
        form.dataset.roomId = result.savedRoomId;
        applyRoomList(result);
        const file = form.elements.photo.files[0];
        if (file) {
            try {
                const uploaded = await uploadCatalogPhoto(file, "room", result.savedRoomId);
                const room = rooms.find((item) => item.id === result.savedRoomId);
                if (room) room.image = uploaded.image;
            } catch (error) {
                throw new Error(`Room details were saved, but the photo could not be uploaded: ${error.message}`);
            }
        }
        modal.hidden = true;
        if (activePage === "rooms") content.innerHTML = renderRooms();
        showToast(isNewRoom ? "Room added." : "Room details saved.");
    }

    async function saveBedspaceForm(form) {
        const result = await requestJson(roomsApi, {
            method: "POST",
            body: JSON.stringify({
                action: "save-bedspace",
                id: form.dataset.bedspaceId,
                roomId: form.dataset.roomId,
                label: form.elements.label.value.trim(),
                status: form.elements.status.value
            })
        });
        const isNewBedspace = !form.dataset.bedspaceId;
        form.dataset.bedspaceId = result.savedBedspaceId;
        applyRoomList(result);
        const file = form.elements.photo.files[0];
        if (file) {
            try {
                const uploaded = await uploadCatalogPhoto(file, "bedspace", result.savedBedspaceId);
                const room = rooms.find((item) => item.id === form.dataset.roomId);
                const bed = room?.beds.find((item) => item.id === result.savedBedspaceId);
                if (bed) bed.image = uploaded.image;
            } catch (error) {
                throw new Error(`Bedspace details were saved, but the photo could not be uploaded: ${error.message}`);
            }
        }
        modal.hidden = true;
        roomDetailModal(form.dataset.roomId);
        showToast(isNewBedspace ? "Bedspace added." : "Bedspace updated.");
    }

    async function toggleRoom(roomId, status) {
        try {
            const result = await requestJson(roomsApi, {
                method: "POST",
                body: JSON.stringify({ action: "toggle-room", roomId, status })
            });
            applyRoomList(result);
            showToast(status === "active" ? "Room activated." : "Room deactivated.");
        } catch (error) {
            showToast(error.message);
        }
    }

    async function deleteBedspace(bedspaceId) {
        const roomId = rooms.find((room) => room.beds.some((bed) => bed.id === bedspaceId))?.id;
        if (!roomId || !window.confirm("Remove this bedspace? This cannot be undone.")) return;
        try {
            const result = await requestJson(roomsApi, {
                method: "POST",
                body: JSON.stringify({ action: "delete-bedspace", bedspaceId })
            });
            applyRoomList(result);
            roomDetailModal(roomId);
            showToast("Bedspace removed.");
        } catch (error) {
            showToast(error.message);
        }
    }

    function roomPhotoModal(roomId) {
        const room = rooms.find((item) => item.id === roomId);
        if (!room) {
            showToast("That room could not be found.");
            return;
        }
        openModal("Edit room photo", `<form data-room-photo-form data-room-id="${esc(room.id)}"><div class="room-photo-preview"><img src="${esc(room.image)}" alt="${esc(room.name)} photo preview" data-room-photo-preview><span>Current room photo</span></div><label class="form-field room-photo-picker">Upload photo<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-room-photo-file required><small>JPG, PNG, or WebP · at least 300 × 200 pixels · maximum 5 MB</small></label><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-primary" disabled>Save room photo</button></div></form>`);
    }

    async function uploadCatalogPhoto(file, targetType, targetId) {
        if (!file) throw new Error("Choose a photo to upload.");
        if (file.size > 5 * 1024 * 1024) throw new Error("Choose an image under 5 MB.");
        const payload = new FormData();
        payload.append("targetType", targetType);
        payload.append(targetType === "room" ? "roomId" : "bedspaceId", targetId);
        payload.append("photo", file);
        const response = await fetch(roomPhotoApi, {
            method: "POST",
            headers: { Accept: "application/json", "X-CSRF-Token": csrfToken },
            body: payload,
            credentials: "same-origin",
            cache: "no-store"
        });
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error("The server returned an unreadable response. Please try again.");
        }
        if (!response.ok) throw new Error(result.error || "The photo could not be uploaded.");
        return result;
    }

    async function uploadRoomPhoto(form) {
        const result = await uploadCatalogPhoto(form.elements.photo.files[0], "room", form.dataset.roomId);
        const room = rooms.find((item) => item.id === result.targetId);
        if (!room) throw new Error("The photo uploaded, but the room could not be refreshed. Reload the page to see it.");
        room.image = result.image;
        modal.hidden = true;
        renderPage();
        showToast("Room photo updated.");
    }

    function detailModal(action, roomId) {
        if (action === "room-detail") return roomDetailModal(roomId);
        const templates = {
            "tenant-detail": ["Tenant details", `<p class="card-copy">Payment history is available in the secured Payments section.</p>`],
            "cleaning-detail": ["Cleaning task details", "<div class='detail-list'><div class='detail-item'><small>Tenant</small><strong>Mikaela Reyes</strong></div><div class='detail-item'><small>Room</small><strong>Room 1</strong></div><div class='detail-item'><small>Task</small><strong>Kitchen</strong></div><div class='detail-item'><small>Assigned date</small><strong>October 24, 2026</strong></div><div class='detail-item'><small>Due date</small><strong>October 28, 2026</strong></div><div class='detail-item'><small>Submitted</small><strong>October 27, 2026 · 4:10 PM</strong></div></div><div class='upload-box' style='margin-top:15px'>▧<span><strong>Submitted proof photo</strong>Click to enlarge</span></div><div class='modal-actions'><button class='btn btn-primary' type='button' data-action='verify-cleaning'>✓ Verify task</button></div>"],
            "application-detail": ["Application · Isabella Flores", "<div class='grid columns-even'><div class='detail-list'><div class='detail-item'><small>Full name</small><strong>Isabella Flores</strong></div><div class='detail-item'><small>Email</small><strong>isabella.flores@example.com</strong></div><div class='detail-item'><small>Contact</small><strong>0917 221 0931</strong></div><div class='detail-item'><small>Room preference</small><strong>Room 2</strong></div><div class='detail-item'><small>Move-in date</small><strong>November 01, 2026</strong></div><div class='detail-item'><small>Emergency contact</small><strong>Rosa Flores · 0920 100 0400</strong></div><div class='detail-item'><small>Submitted date</small><strong>October 27, 2026</strong></div><div class='detail-item'><small>About</small><strong>Student looking for a safe, quiet place near campus.</strong></div></div><div><div class='upload-box' style='height:140px' data-action='id-preview'>▧<span><strong>Government ID</strong>Valid ID · Click to enlarge</span></div><p class='card-meta' style='margin:5px 0'>ID type: Government-issued photo ID</p><div class='upload-box' style='height:120px;margin-top:10px' data-action='id-preview'>▧<span><strong>Live capture</strong>Face verification photo</span></div><div class='face-result' style='margin-top:10px'>✓ Match passed <strong>87% similarity</strong></div></div></div><div class='modal-actions'><button class='btn btn-danger' type='button' data-action='reject-application'>Reject</button><button class='btn btn-light' type='button' data-action='approve-application'>Approve</button><button class='btn btn-primary' type='button' data-action='activate-account'>Activate account</button></div>"],
            "inquiry-detail": ["Inquiry · Jamie Cruz", "<div class='detail-list'><div class='detail-item'><small>Visitor name</small><strong>Jamie Cruz</strong></div><div class='detail-item'><small>Contact number</small><strong>0917 123 4400</strong></div><div class='detail-item'><small>Submitted</small><strong>October 27, 2026</strong></div><div class='detail-item'><small>Room interest</small><strong>Room 2 · Bed B</strong></div></div><p class='card-copy'>Hi! I would like to know if this bed is available for November.</p><label class='form-field'>Reply<textarea placeholder='Write a response...'></textarea></label><div class='modal-actions'><button class='btn btn-primary' type='button' data-action='reply-inquiry'>Send reply</button></div>"]
        };
        const data = templates[action];
        if (data) openModal(data[0], data[1], action === "application-detail");
    }

    function onAction(action, actionElement) {
        if (action === "apply-report") return loadReports();
        if (action === "export-report") return exportReport();
        if (action === "add-room") return roomFormModal();
        if (action === "edit-room") return roomFormModal(actionElement.dataset.room);
        if (action === "toggle-room") return toggleRoom(actionElement.dataset.room, actionElement.dataset.status);
        if (action === "add-bedspace") return bedspaceFormModal(actionElement.dataset.room);
        if (action === "edit-bedspace") return bedspaceFormModal(actionElement.dataset.room, actionElement.dataset.bedspace);
        if (action === "delete-bedspace") return deleteBedspace(actionElement.dataset.bedspace);
        if (action === "add-payment") return openPaymentModal();
        if (action === "tenant-add-payment") return openTenantPaymentModal();
        if (action === "accept-submission") {
            if (window.confirm("Confirm that you verified this payment with the tenant or GCash transaction. This will post it as a confirmed payment and create a receipt.")) {
                return reviewTenantPayment(actionElement.dataset.submissionId, "accept");
            }
            return;
        }
        if (action === "decline-submission") {
            const submissionId = actionElement.dataset.submissionId;
            return openModal("Decline payment request", `<form data-review-submission-form data-submission-id="${esc(submissionId)}" data-decision="decline"><p class="card-copy">Declining leaves the rent balance unchanged. Add a short note so the tenant understands what to do next.</p><label class="form-field">Note for tenant<textarea name="reviewNote" maxlength="500" placeholder="For example: Reference number could not be verified." required></textarea></label><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-danger">Decline request</button></div></form>`);
        }
        if (action === "add-tenant") return openTenantModal();
        if (action === "receipt") return showReceipt(actionElement.dataset.paymentId);
        if (action === "verify-payment") {
            if (window.confirm("Verify that this payment has been received?")) return changePaymentStatus("confirm", actionElement.dataset.paymentId);
            return;
        }
        if (action === "void-payment") {
            if (window.confirm("Void this pending payment? This action will be recorded in the audit history.")) return changePaymentStatus("void", actionElement.dataset.paymentId);
            return;
        }
        if (action === "reverse-payment") {
            const paymentId = actionElement.dataset.paymentId;
            return openModal("Reverse confirmed payment", `<form data-reverse-payment-form data-payment-id="${esc(paymentId)}"><p class="card-copy">Confirmed payment values cannot be edited. Reversing preserves the original transaction and records a correction reason.</p><label class="form-field">Reason for reversal<textarea name="reason" minlength="5" maxlength="500" required></textarea></label><div data-form-error class="form-error" hidden></div><div class="modal-actions"><button type="button" class="btn btn-light" data-close>Cancel</button><button type="submit" class="btn btn-danger">Reverse payment</button></div></form>`);
        }
        if (action === "edit-payment") return openPendingPaymentEdit(actionElement.dataset.paymentId);
        const forms = {
            "edit-tenant": ["Edit tenant", [["Full name", "text", "Full name"], ["Contact number", "tel", "09xx xxx xxxx"], ["Emergency contact", "text", "Emergency contact"], ["Login email", "email", "name@example.com"], ["Assign room", "select"], ["Assign bedspace", "select"], ["Move-in date", "date"], ["Status", "select"]]],
            "assign-cleaning": ["Assign cleaning task", [["Select tenant", "select"], ["Task name", "text", "Kitchen, CR 1 & CR 2, Living Area"], ["Assigned date", "date"], ["Due date", "date"]]],
            "add-announcement": ["New announcement", [["Title", "text", "Announcement title"], ["Content", "textarea", "Write your announcement"], ["Type", "select"], ["Status", "select"]]],
            "edit-announcement": ["Edit announcement", [["Title", "text", "Announcement title"], ["Content", "textarea", "Announcement details"], ["Type", "select"], ["Status", "select"]]],
            "add-rule": ["Add house rule", [["Rule title", "text", "Rule title"], ["Description", "textarea", "Describe the rule"], ["Status", "select"]]],
            "edit-rule": ["Edit house rule", [["Rule title", "text", "Rule title"], ["Description", "textarea", "Describe the rule"], ["Status", "select"]]],
            "report-issue": ["Report a maintenance issue", [["Issue title", "text", "Brief issue title"], ["Description", "textarea", "Describe the issue"], ["Photo upload", "file"]]],
            "reply-inquiry": ["Reply to inquiry", [["Response", "textarea", "Write your response"]]]
        };
        if (action === "edit-room" || action === "change-room-photo") return roomPhotoModal(actionElement.dataset.room);
        if (forms[action]) return formModal(...forms[action]);
        if (["room-detail", "tenant-detail", "cleaning-detail", "application-detail", "inquiry-detail"].includes(action)) return detailModal(action, actionElement.dataset.room);
        if (action === "activate-account") {
            openModal("Tenant account created", "<div class='policy-banner'>Deliver temporary credentials securely and ask the tenant to change the password after their first sign-in.</div><div class='detail-list'><div class='detail-item'><small>Email address</small><strong>hannah.v@example.com</strong></div><div class='detail-item'><small>Temporary password</small><strong>Hannah-2026-Temp!</strong></div></div><div class='modal-actions'><button class='btn btn-primary' type='button' data-close>Done</button></div>");
            return;
        }
        if (action === "application-guide") return openModal("Application review", "<p class='card-copy'>Review the applicant details and uploaded ID. Check the live capture and face match result. Approve or reject pending applications. Activate an account only after successful verification.</p>");
        if (action === "verify-cleaning") return showToast("Cleaning verification is not connected yet.");
        if (action === "submit-proof") return showToast("Proof submission is not connected yet.");
        if (action === "toggle-status") return showToast("Tenant status updates are not connected yet.");
        if (action === "approve-application") return showToast("Application approval is not connected yet.");
        if (action === "reject-application") return showToast("Application rejection is not connected yet.");
        if (action === "submit-feedback") return showToast(selectedStars ? "Feedback submission is not connected yet." : "Choose a star rating first.");
        if (action === "print") return window.print();
        if (action === "photo-detail" || action === "id-preview") return openModal(action === "photo-detail" ? "Cleaning proof photo" : "Photo details", "<div class='upload-box' style='min-height:220px'><span class='proof-icon'>▧</span><span><strong>Photo unavailable</strong>No uploaded photo is attached to this record.</span></div>");
        if (action === "assign-bed") return showToast("Bed assignment updates are not connected yet.");
        if (action === "toggle-announcement" || action === "toggle-rule" || action === "delete-item") return showToast("This action is not connected yet.");
        if (action === "maintenance-detail") return openModal("Maintenance request", "<p class='card-copy'>This request is assigned to the property team. Status and resolution details can be edited in the maintenance card.</p>");
        showToast("This action is not connected yet.");
    }

    document.addEventListener("click", (event) => {
        const interactive = event.target.closest(".btn, .side-link, .icon-button, .icon-action, .filter-button, .theme-toggle, .star-button");
        if (interactive && !interactive.disabled) {
            const bounds = interactive.getBoundingClientRect();
            interactive.style.setProperty("--click-x", `${event.clientX ? event.clientX - bounds.left : bounds.width / 2}px`);
            interactive.style.setProperty("--click-y", `${event.clientY ? event.clientY - bounds.top : bounds.height / 2}px`);
            interactive.classList.remove("click-ripple");
            void interactive.offsetWidth;
            interactive.classList.add("click-ripple");
            window.setTimeout(() => interactive.classList.remove("click-ripple"), 460);
        }
        const pageButton = event.target.closest("[data-page]");
        if (pageButton) {
            event.preventDefault();
            activePage = pageButton.dataset.page;
            activeFilter = "All";
            renderPage();
            return;
        }
        const paymentStatus = event.target.closest("[data-payment-status]");
        if (paymentStatus) {
            activeFilter = paymentStatus.dataset.paymentStatus;
            content.querySelectorAll("[data-payment-status]").forEach((button) => button.classList.toggle("active", button === paymentStatus));
            loadPayments();
            return;
        }
        const filterButton = event.target.closest("[data-filter]");
        if (filterButton) {
            activeFilter = filterButton.dataset.filter;
            content.querySelectorAll("[data-filter]").forEach((button) => button.classList.toggle("active", button === filterButton));
            if (activePage === "rooms") {
                content.querySelectorAll("[data-room-card]").forEach((card) => {
                    card.hidden = activeFilter !== "All" && card.dataset.roomStatus !== activeFilter.toLowerCase();
                });
                return;
            }
            const filter = activeFilter.toLowerCase();
            content.querySelectorAll("tbody tr, .room-card, .inquiry-card, .announcement-card, .maintenance-card").forEach((item) => {
                item.hidden = filter !== "all" && !item.textContent.toLowerCase().includes(filter);
            });
            return;
        }
        const star = event.target.closest("[data-star]");
        if (star) {
            selectedStars = Number(star.dataset.star);
            content.querySelectorAll("[data-star]").forEach((button) => button.classList.toggle("selected", Number(button.dataset.star) <= selectedStars));
            const labels = ["", "Poor", "Fair", "Good", "Very Good", "Excellent"];
            document.getElementById("rating-label").textContent = labels[selectedStars];
            return;
        }
        const action = event.target.closest("[data-action]");
        if (action) return onAction(action.dataset.action, action);
        if (event.target.closest("[data-close]") || event.target.classList.contains("modal-close") || event.target === modal) modal.hidden = true;
    });

    document.addEventListener("input", (event) => {
        if (event.target.matches("[data-payment-search]")) {
            window.clearTimeout(paymentFilterTimer);
            paymentFilterTimer = window.setTimeout(() => loadPayments(), 250);
            return;
        }
        if (event.target.matches("[data-tenant-search]")) {
            const term = event.target.value.toLowerCase().trim();
            content.querySelectorAll("[data-tenant-row]").forEach((row) => { row.hidden = !row.dataset.search.includes(term); });
            return;
        }
        if (!event.target.matches("[data-search-input]")) return;
        const term = event.target.value.toLowerCase().trim();
        content.querySelectorAll("[data-search]").forEach((item) => { item.hidden = !item.dataset.search.includes(term); });
        content.querySelectorAll("tbody tr").forEach((item) => { item.hidden = !item.textContent.toLowerCase().includes(term); });
    });
    document.addEventListener("change", (event) => {
        if (event.target.matches("[data-payment-month], [data-payment-method]")) {
            loadPayments();
            return;
        }
        if (event.target.matches("[data-room-photo-file], [data-catalog-photo-file]")) {
            const form = event.target.form;
            const file = event.target.files[0];
            const submit = form.querySelector('[type="submit"]');
            const preview = form.querySelector("[data-room-photo-preview], [data-catalog-photo-preview]");
            const placeholder = form.querySelector("[data-photo-placeholder]");
            clearFormErrors(form);
            if (!file) {
                if (event.target.matches("[data-room-photo-file]")) submit.disabled = true;
                return;
            }
            if ((file.type && !["image/jpeg", "image/png", "image/webp"].includes(file.type)) || file.size > 5 * 1024 * 1024) {
                showFormError(form, new Error("Choose a JPG, PNG, or WebP image under 5 MB."));
                if (event.target.matches("[data-room-photo-file]")) submit.disabled = true;
                return;
            }
            if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
            preview.dataset.objectUrl = URL.createObjectURL(file);
            preview.src = preview.dataset.objectUrl;
            preview.hidden = false;
            if (placeholder) placeholder.hidden = true;
            if (event.target.matches("[data-room-photo-file]")) submit.disabled = false;
            return;
        }
        if (event.target.matches("[data-inline]")) showToast("Updates are not connected yet.");
        if (event.target.matches("[data-demo-upload]") && event.target.files.length) showToast("Photo uploads are not connected yet.");
    });
    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (form.matches("[data-room-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            saveRoomForm(form)
                .catch((error) => showFormError(form, error))
                .finally(() => {
                    if (form.isConnected) submit.disabled = false;
                });
            return;
        }
        if (form.matches("[data-bedspace-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            saveBedspaceForm(form)
                .catch((error) => showFormError(form, error))
                .finally(() => {
                    if (form.isConnected) submit.disabled = false;
                });
            return;
        }
        if (form.matches("[data-room-photo-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            uploadRoomPhoto(form)
                .catch((error) => showFormError(form, error))
                .finally(() => {
                    if (form.isConnected) submit.disabled = !form.elements.photo.files.length;
                });
            return;
        }
        if (form.matches("[data-tenant-payment-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            const payload = {
                action: "submit-tenant-payment",
                submissionId: form.dataset.submissionId,
                billingMonth: form.elements.billingMonth.value,
                amount: form.elements.amount.value,
                paymentMethod: form.elements.paymentMethod.value,
                referenceNumber: form.elements.referenceNumber.value.trim(),
                paymentDate: form.elements.paymentDate.value
            };
            requestJson(paymentApi, { method: "POST", body: JSON.stringify(payload) })
                .then(async () => {
                    modal.hidden = true;
                    showToast("Payment request submitted. It will remain pending until an administrator verifies it.");
                    await loadPayments();
                })
                .catch((requestError) => showFormError(form, requestError))
                .finally(() => { submit.disabled = false; });
            return;
        }
        if (form.matches("[data-review-submission-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            requestJson(paymentApi, { method: "POST", body: JSON.stringify({
                action: "review-submission",
                submissionId: form.dataset.submissionId,
                decision: form.dataset.decision,
                reviewNote: form.elements.reviewNote.value.trim()
            }) }).then(async () => {
                modal.hidden = true;
                showToast("Payment request declined.");
                await loadPayments();
            }).catch((requestError) => showFormError(form, requestError))
                .finally(() => { submit.disabled = false; });
            return;
        }
        if (form.matches("[data-payment-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            const payload = {
                action: "create",
                tenantId: Number(form.elements.tenantId.value),
                billingMonth: form.elements.billingMonth.value,
                amountReceived: form.elements.amountReceived.value,
                paymentMethod: form.elements.paymentMethod.value,
                referenceNumber: form.elements.referenceNumber.value.trim(),
                paymentDate: form.elements.paymentDate.value,
                notes: form.elements.notes.value.trim(),
                confirmCashReceived: form.elements.paymentMethod.value === "Cash" && form.elements.confirmCashReceived.checked,
                idempotencyKey: form.dataset.idempotencyKey
            };
            requestJson(paymentApi, { method: "POST", body: JSON.stringify(payload) })
                .then(async (result) => {
                    modal.hidden = true;
                    showToast(`Payment ${result.receiptNumber} recorded as ${result.status || "Pending"}.`);
                    await loadPayments();
                    await showReceipt(result.id);
                })
                .catch((requestError) => {
                    showFormError(form, requestError);
                })
                .finally(() => { submit.disabled = Math.round(Number(form.dataset.available) * 100) <= 0; });
            return;
        }
        if (form.matches("[data-tenant-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            const payload = {
                fullName: form.elements.fullName.value.trim(),
                phone: form.elements.phone.value.trim(),
                emergencyContact: form.elements.emergencyContact.value.trim(),
                email: form.elements.email.value.trim(),
                roomId: form.elements.roomId.value,
                bedspaceId: form.elements.bedspaceId.value,
                moveInDate: form.elements.moveInDate.value,
                monthlyRent: form.elements.monthlyRent.value
            };
            requestJson(tenantApi, { method: "POST", body: JSON.stringify(payload) })
                .then(async (result) => {
                    modal.hidden = true;
                    showToast("Tenant account created. Share the temporary password securely.");
                    if (activePage === "tenants") await loadTenants();
                    if (activePage === "payments") await loadPayments();
                    openModal("Tenant account created", `<div class="policy-banner">Share this temporary password with the tenant through a secure channel. It will only be shown once; the tenant must change it at first sign-in.</div><div class="detail-list"><div class="detail-item"><small>Email address</small><strong>${esc(result.tenant.email)}</strong></div><div class="detail-item"><small>Temporary password</small><strong class="temporary-password">${esc(result.temporaryPassword)}</strong></div></div><div class="modal-actions"><button class="btn btn-primary" type="button" data-close>Done</button></div>`);
                })
                .catch((requestError) => {
                    showFormError(form, requestError);
                })
                .finally(() => { submit.disabled = false; });
            return;
        }
        if (form.matches("[data-edit-payment-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            requestJson(paymentApi, { method: "POST", body: JSON.stringify({
                action: "edit-pending",
                paymentId: form.dataset.paymentId,
                amountReceived: form.elements.amountReceived.value,
                paymentMethod: form.elements.paymentMethod.value,
                referenceNumber: form.elements.referenceNumber.value.trim(),
                paymentDate: form.elements.paymentDate.value,
                notes: form.elements.notes.value.trim()
            }) }).then(async () => {
                modal.hidden = true;
                showToast("Pending payment updated and audit logged.");
                await loadPayments();
            }).catch((requestError) => {
                showFormError(form, requestError);
            }).finally(() => { submit.disabled = false; });
            return;
        }
        if (form.matches("[data-reverse-payment-form]")) {
            event.preventDefault();
            const submit = form.querySelector('[type="submit"]');
            clearFormErrors(form);
            submit.disabled = true;
            changePaymentStatus("reverse", form.dataset.paymentId, form.elements.reason.value.trim(), (requestError) => showFormError(form, requestError))
                .finally(() => { submit.disabled = false; });
            return;
        }
        if (!form.matches("[data-demo-form]")) return;
        event.preventDefault();
        modal.hidden = true;
        showToast("Form submission is not connected yet.");
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") modal.hidden = true;
    });
    setTheme(document.documentElement.dataset.theme === "dark" ? "dark" : "light");
    themeToggle.addEventListener("click", () => {
        setTheme(document.documentElement.dataset.theme === "dark" ? "light" : "dark", true);
    });
    document.getElementById("menu-toggle").addEventListener("click", () => document.getElementById("sidebar").classList.toggle("open"));
    const sidebarCollapseToggle = document.getElementById("sidebar-collapse-toggle");
    function updateSidebarCollapsedState(isCollapsed) {
        document.documentElement.dataset.sidebarCollapsed = String(isCollapsed);
        sidebarCollapseToggle.setAttribute("aria-expanded", String(!isCollapsed));
        sidebarCollapseToggle.setAttribute("aria-label", `${isCollapsed ? "Expand" : "Collapse"} sidebar`);
        sidebarCollapseToggle.title = `${isCollapsed ? "Expand" : "Collapse"} sidebar`;
        try {
            localStorage.setItem("bamstay-sidebar-collapsed", String(isCollapsed));
        } catch {}
    }
    updateSidebarCollapsedState(document.documentElement.dataset.sidebarCollapsed === "true");
    sidebarCollapseToggle.addEventListener("click", () => {
        updateSidebarCollapsedState(document.documentElement.dataset.sidebarCollapsed !== "true");
    });
    modal.querySelector(".modal-close").addEventListener("click", () => { modal.hidden = true; });
    renderPage();
})();
