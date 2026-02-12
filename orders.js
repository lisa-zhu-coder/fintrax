// Gestión de pedidos a Sardinha de Artesanato S.L.
const ORDERS_STORAGE_KEY = 'miramira_orders';

class OrderManager {
  constructor() {
    this.orders = this.loadOrders();
    this.initializeUI();
  }

  loadOrders() {
    const stored = localStorage.getItem(ORDERS_STORAGE_KEY);
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch (e) {
        console.error('Error cargando pedidos:', e);
        return [];
      }
    }
    return [];
  }

  saveOrders() {
    localStorage.setItem(ORDERS_STORAGE_KEY, JSON.stringify(this.orders));
  }

  initializeUI() {
    // Crear enlace en el menú del sidebar
    // Esperar a que el DOM esté listo
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => this.createMenuLink(), 100);
      });
    } else {
      setTimeout(() => this.createMenuLink(), 100);
    }
  }

  createMenuLink() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
      console.warn('Sidebar no encontrado, reintentando...');
      setTimeout(() => this.createMenuLink(), 500);
      return;
    }

    // Buscar si ya existe el enlace
    if (document.getElementById('navMiramiraPedidos')) {
      console.log('Enlace de Pedidos ya existe');
      return;
    }

    // Buscar el enlace de Empleados para insertar después
    const empleadosLink = Array.from(sidebar.querySelectorAll('a')).find(a => 
      a.textContent && a.textContent.includes('Empleados')
    );

    // Si no se encuentra Empleados, buscar otros enlaces como referencia
    let referenceLink = empleadosLink;
    if (!referenceLink) {
      // Buscar cualquier enlace en el sidebar como referencia
      const allLinks = Array.from(sidebar.querySelectorAll('a'));
      referenceLink = allLinks[allLinks.length - 1]; // Último enlace
    }

    // Crear el enlace en el menú
    const link = document.createElement('a');
    link.href = '#';
    link.id = 'navMiramiraPedidos';
    link.className = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50';
    link.innerHTML = `
      <span class="text-slate-500" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M9 2v4M15 2v4M9 18h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      Pedidos
    `;

    link.addEventListener('click', (e) => {
      e.preventDefault();
      // Usar showView de app.js si está disponible
      if (typeof showView === 'function') {
        showView('miramiraPedidos');
      } else {
        this.showOrdersView();
      }
    });

    // Insertar después del enlace de referencia o al final del sidebar
    if (referenceLink && referenceLink.parentNode) {
      referenceLink.parentNode.insertBefore(link, referenceLink.nextSibling);
      console.log('Enlace de Pedidos añadido después de:', referenceLink.textContent);
    } else {
      sidebar.appendChild(link);
      console.log('Enlace de Pedidos añadido al final del sidebar');
    }
  }

  showOrdersView() {
    // Usar el sistema de vistas de app.js si está disponible
    if (typeof showView === 'function') {
      showView('miramiraPedidos');
    } else {
      // Fallback: ocultar todas las vistas manualmente
      const views = document.querySelectorAll('[id^="view"]');
      views.forEach(view => view.classList.add('hidden'));
    }

    // Mostrar la vista de pedidos
    const ordersView = document.getElementById('viewMiramiraPedidos');
    if (ordersView) {
      ordersView.classList.remove('hidden');
      this.populateStoreFilter();
      this.renderOrdersSummary();
      this.renderOrdersTable();
      this.initializeEventListeners();
    } else {
      // Si no existe, crear la vista
      this.createOrdersView();
    }
  }

  createOrdersView() {
    const mainContent = document.getElementById('mainContent');
    if (!mainContent) return;

    // Verificar si la vista ya existe
    const existingView = document.getElementById('viewMiramiraPedidos');
    if (existingView) {
      existingView.classList.remove('hidden');
      this.populateStoreFilter();
      this.renderOrdersSummary();
      this.renderOrdersTable();
      this.initializeEventListeners();
      return;
    }

    // Buscar el contenedor de vistas
    const viewsContainer = mainContent.querySelector('main');
    if (!viewsContainer) {
      console.error('No se encontró el contenedor main para la vista de pedidos');
      return;
    }
    
    const view = document.createElement('section');
    view.id = 'viewMiramiraPedidos';
    view.className = 'space-y-6';
    view.innerHTML = `
      <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Pedidos - Sardinha de Artesanato S.L.</h1>
            <p class="text-sm text-slate-500">Gestiona todos los pedidos realizados al proveedor</p>
          </div>
          ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('orders.main.create') ? `
            <button
              id="addOrderBtn"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Añadir pedido
            </button>
          ` : ''}
        </div>
      </header>

      <!-- Resumen por tienda -->
      <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Resumen por tienda</h2>
            <p class="text-xs text-slate-500">Total de pedidos, importe pagado y pendiente por tienda</p>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
              <tr>
                <th class="whitespace-nowrap px-3 py-3 font-semibold">Tienda</th>
                <th class="whitespace-nowrap px-3 py-3 text-right font-semibold">Total Pedidos</th>
                <th class="whitespace-nowrap px-3 py-3 text-right font-semibold">Importe Total (€)</th>
                <th class="whitespace-nowrap px-3 py-3 text-right font-semibold">Importe Pagado (€)</th>
                <th class="whitespace-nowrap px-3 py-3 text-right font-semibold">Importe Pendiente (€)</th>
              </tr>
            </thead>
            <tbody id="ordersSummaryTbody" class="divide-y divide-slate-100">
              <!-- Se llena dinámicamente -->
            </tbody>
            <tfoot class="border-t-2 border-slate-200 bg-slate-50">
              <tr>
                <td class="whitespace-nowrap px-3 py-3 font-semibold text-slate-900">TOTAL</td>
                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900" id="summaryTotalOrders">0</td>
                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900" id="summaryTotalAmount">0,00 €</td>
                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-emerald-700" id="summaryTotalPaid">0,00 €</td>
                <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-amber-700" id="summaryTotalPending">0,00 €</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Filtros -->
      <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Tienda</span>
            <select
              id="filterOrdersTienda"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="ALL">Todas las tiendas</option>
              <!-- Se llena dinámicamente -->
            </select>
          </label>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Período</span>
            <select
              id="filterOrdersPeriodo"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="last_7">Últimos 7 días</option>
              <option value="last_30">Últimos 30 días</option>
              <option value="custom">Fecha personalizada</option>
            </select>
          </label>
          <div id="filterOrdersCustomDates" class="hidden grid-cols-2 gap-2 md:col-span-2 lg:col-span-3">
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Fecha desde</span>
              <input
                type="date"
                id="filterOrdersFechaDesde"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              />
            </label>
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Fecha hasta</span>
              <input
                type="date"
                id="filterOrdersFechaHasta"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              />
            </label>
          </div>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Forma de pago</span>
            <select
              id="filterOrdersPaymentMethod"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="">Todas</option>
              <option value="cash">Efectivo</option>
              <option value="bank">Banco</option>
              <option value="transfer">Transferencia</option>
              <option value="card">Tarjeta</option>
            </select>
          </label>
        </div>
      </div>

      <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
              <tr>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="estado" data-sort-dir="">
                  Estado
                  <span class="ml-1 text-slate-400" data-sort-indicator="estado"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="fecha" data-sort-dir="desc">
                  Fecha
                  <span class="ml-1 text-slate-400" data-sort-indicator="fecha">↓</span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="tienda" data-sort-dir="">
                  Tienda
                  <span class="ml-1 text-slate-400" data-sort-indicator="tienda"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="factura" data-sort-dir="">
                  Nº Factura
                  <span class="ml-1 text-slate-400" data-sort-indicator="factura"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="pedido" data-sort-dir="">
                  Nº Pedido
                  <span class="ml-1 text-slate-400" data-sort-indicator="pedido"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="concepto" data-sort-dir="">
                  Concepto
                  <span class="ml-1 text-slate-400" data-sort-indicator="concepto"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 text-right cursor-pointer select-none hover:bg-slate-50" data-sort="importe" data-sort-dir="">
                  Importe (€)
                  <span class="ml-1 text-slate-400" data-sort-indicator="importe"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 text-right cursor-pointer select-none hover:bg-slate-50" data-sort="pendiente" data-sort-dir="">
                  Pendiente (€)
                  <span class="ml-1 text-slate-400" data-sort-indicator="pendiente"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2 cursor-pointer select-none hover:bg-slate-50" data-sort="pago" data-sort-dir="">
                  Forma de pago
                  <span class="ml-1 text-slate-400" data-sort-indicator="pago"></span>
                </th>
                <th class="whitespace-nowrap px-3 py-2"></th>
              </tr>
            </thead>
            <tbody id="ordersTbody" class="divide-y divide-slate-100">
              <!-- rows injected -->
            </tbody>
          </table>
        </div>
      </div>
    `;

    viewsContainer.appendChild(view);
    this.populateStoreFilter();
    this.renderOrdersSummary();
    this.renderOrdersTable();
    this.initializeEventListeners();
  }

  renderOrdersSummary() {
    const tbody = document.getElementById('ordersSummaryTbody');
    if (!tbody) return;

    // Calcular resumen por tienda
    const summaryByStore = {};
    let totalOrders = 0;
    let totalAmount = 0;
    let totalPaid = 0;
    let totalPending = 0;

    this.orders.forEach(order => {
      const storeId = order.storeId || 'sin-tienda';
      const storeName = storeId && typeof STORES !== 'undefined' 
        ? (STORES.find(s => s.id === storeId)?.name || storeId)
        : (storeId === 'sin-tienda' ? 'Sin tienda' : storeId);

      if (!summaryByStore[storeId]) {
        summaryByStore[storeId] = {
          name: storeName,
          orders: 0,
          totalAmount: 0,
          totalPaid: 0,
          totalPending: 0
        };
      }

      const orderAmount = parseFloat(order.amount) || 0;
      const paid = this.getTotalPaid(order);
      const pending = this.getPendingAmount(order);

      summaryByStore[storeId].orders += 1;
      summaryByStore[storeId].totalAmount += orderAmount;
      summaryByStore[storeId].totalPaid += paid;
      summaryByStore[storeId].totalPending += pending;

      totalOrders += 1;
      totalAmount += orderAmount;
      totalPaid += paid;
      totalPending += pending;
    });

    // Renderizar tabla
    tbody.innerHTML = '';

    // Ordenar por nombre de tienda
    const storesArray = Object.values(summaryByStore).sort((a, b) => 
      a.name.localeCompare(b.name)
    );

    if (storesArray.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td class="px-3 py-6 text-center text-slate-500" colspan="5">
            No hay pedidos registrados.
          </td>
        </tr>
      `;
    } else {
      storesArray.forEach(store => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50';
        tr.innerHTML = `
          <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900">${this.escapeHtml(store.name)}</td>
          <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">${store.orders}</td>
          <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900">${this.formatEuro(store.totalAmount)}</td>
          <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-emerald-700">${this.formatEuro(store.totalPaid)}</td>
          <td class="whitespace-nowrap px-3 py-3 text-right font-semibold ${store.totalPending > 0 ? 'text-amber-700' : 'text-emerald-700'}">${this.formatEuro(store.totalPending)}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    // Actualizar totales
    const totalOrdersEl = document.getElementById('summaryTotalOrders');
    const totalAmountEl = document.getElementById('summaryTotalAmount');
    const totalPaidEl = document.getElementById('summaryTotalPaid');
    const totalPendingEl = document.getElementById('summaryTotalPending');

    if (totalOrdersEl) totalOrdersEl.textContent = totalOrders;
    if (totalAmountEl) totalAmountEl.textContent = this.formatEuro(totalAmount);
    if (totalPaidEl) totalPaidEl.textContent = this.formatEuro(totalPaid);
    if (totalPendingEl) {
      totalPendingEl.textContent = this.formatEuro(totalPending);
      totalPendingEl.className = totalPending > 0 
        ? 'whitespace-nowrap px-3 py-3 text-right font-semibold text-amber-700'
        : 'whitespace-nowrap px-3 py-3 text-right font-semibold text-emerald-700';
    }
  }

  populateStoreFilter() {
    const filterSelect = document.getElementById('filterOrdersTienda');
    if (!filterSelect) return;

    // Guardar el valor actual
    const currentValue = filterSelect.value;

    // Limpiar y añadir opción "Todas"
    filterSelect.innerHTML = '<option value="ALL">Todas las tiendas</option>';

    // Obtener tiendas disponibles (similar a ingresos/gastos)
    const assignedStore = typeof authManager !== 'undefined' && authManager ? authManager.getAssignedStore() : null;
    const availableStores = assignedStore 
      ? (typeof STORES !== 'undefined' ? STORES.filter((s) => s.id === assignedStore) : [])
      : (typeof STORES !== 'undefined' ? STORES : []);

    // Añadir opciones de tiendas
    availableStores.forEach(store => {
      const option = document.createElement('option');
      option.value = store.id;
      option.textContent = store.name;
      filterSelect.appendChild(option);
    });

    // Restaurar el valor anterior si existe
    if (currentValue && Array.from(filterSelect.options).some(opt => opt.value === currentValue)) {
      filterSelect.value = currentValue;
    }
  }

  // Calcular el total pagado de un pedido
  getTotalPaid(order) {
    if (!order.payments || !Array.isArray(order.payments) || order.payments.length === 0) {
      // Compatibilidad con datos antiguos
      if (order.paymentAmount) {
        return parseFloat(order.paymentAmount) || 0;
      }
      return 0;
    }
    return order.payments.reduce((sum, payment) => sum + (parseFloat(payment.amount) || 0), 0);
  }

  // Calcular el importe pendiente
  getPendingAmount(order) {
    const totalAmount = parseFloat(order.amount) || 0;
    const totalPaid = this.getTotalPaid(order);
    return Math.max(0, totalAmount - totalPaid);
  }

  // Obtener el estado del pedido
  getOrderStatus(order) {
    const totalAmount = parseFloat(order.amount) || 0;
    const totalPaid = this.getTotalPaid(order);
    return totalPaid >= totalAmount ? 'pagado' : 'pendiente';
  }

  filterAndSortOrders(orders, sortField = null, sortDir = null) {
    let filtered = [...orders];

    // Filtro por tienda
    const filterSelect = document.getElementById('filterOrdersTienda');
    if (filterSelect && filterSelect.value && filterSelect.value !== 'ALL') {
      filtered = filtered.filter(order => order.storeId === filterSelect.value);
    }

    // Filtro por período
    let fechaDesde = null;
    let fechaHasta = null;
    const periodoSelect = document.getElementById('filterOrdersPeriodo');
    if (periodoSelect) {
      const periodo = periodoSelect.value;
      if (periodo === 'custom') {
        const fechaDesdeInput = document.getElementById('filterOrdersFechaDesde');
        const fechaHastaInput = document.getElementById('filterOrdersFechaHasta');
        if (fechaDesdeInput && fechaDesdeInput.value) {
          fechaDesde = fechaDesdeInput.value;
        }
        if (fechaHastaInput && fechaHastaInput.value) {
          fechaHasta = fechaHastaInput.value;
        }
      } else if (periodo === 'last_7') {
        const end = new Date();
        end.setHours(23, 59, 59, 999);
        const start = new Date(end);
        start.setDate(start.getDate() - 6);
        fechaDesde = start.toISOString().split('T')[0];
        fechaHasta = end.toISOString().split('T')[0];
      } else if (periodo === 'last_30') {
        const end = new Date();
        end.setHours(23, 59, 59, 999);
        const start = new Date(end);
        start.setDate(start.getDate() - 29);
        fechaDesde = start.toISOString().split('T')[0];
        fechaHasta = end.toISOString().split('T')[0];
      }
    }
    if (fechaDesde) {
      filtered = filtered.filter(order => order.date >= fechaDesde);
    }
    if (fechaHasta) {
      filtered = filtered.filter(order => order.date <= fechaHasta);
    }

    // Filtro por forma de pago
    const paymentMethodSelect = document.getElementById('filterOrdersPaymentMethod');
    if (paymentMethodSelect && paymentMethodSelect.value) {
      const paymentMethod = paymentMethodSelect.value;
      filtered = filtered.filter(order => {
        if (!order.payments || !Array.isArray(order.payments) || order.payments.length === 0) {
          // Compatibilidad con datos antiguos
          return order.paymentMethod === paymentMethod;
        }
        // Verificar si alguno de los pagos tiene la forma de pago seleccionada
        return order.payments.some(payment => payment.method === paymentMethod);
      });
    }

    // Ordenación
    const sortFieldToUse = sortField || (window.sortOrdersField || 'fecha');
    const sortDirToUse = sortDir !== null ? sortDir : (window.sortOrdersDir !== undefined ? window.sortOrdersDir : 'desc');

    filtered.sort((a, b) => {
      let aVal, bVal;

      switch (sortFieldToUse) {
        case 'fecha':
          aVal = a.date || '';
          bVal = b.date || '';
          return sortDirToUse === 'desc' 
            ? bVal.localeCompare(aVal) || (b.updatedAt || '').localeCompare(a.updatedAt || '')
            : aVal.localeCompare(bVal) || (a.updatedAt || '').localeCompare(b.updatedAt || '');
        
        case 'tienda':
          const aStore = a.storeId ? (typeof STORES !== 'undefined' ? STORES.find(s => s.id === a.storeId)?.name || a.storeId : a.storeId) : '';
          const bStore = b.storeId ? (typeof STORES !== 'undefined' ? STORES.find(s => s.id === b.storeId)?.name || b.storeId : b.storeId) : '';
          return sortDirToUse === 'asc' 
            ? aStore.localeCompare(bStore)
            : bStore.localeCompare(aStore);
        
        case 'factura':
          aVal = a.invoiceNumber || '';
          bVal = b.invoiceNumber || '';
          return sortDirToUse === 'asc' 
            ? aVal.localeCompare(bVal)
            : bVal.localeCompare(aVal);
        
        case 'pedido':
          aVal = a.orderNumber || '';
          bVal = b.orderNumber || '';
          return sortDirToUse === 'asc' 
            ? aVal.localeCompare(bVal)
            : bVal.localeCompare(aVal);
        
        case 'concepto':
          const conceptLabels = {
            'pedido': 'Pedido',
            'royalty': 'Royalty',
            'rectificacion': 'Rectificación',
            'tara': 'Tara'
          };
          aVal = conceptLabels[a.concept] || a.concept || '';
          bVal = conceptLabels[b.concept] || b.concept || '';
          return sortDirToUse === 'asc' 
            ? aVal.localeCompare(bVal)
            : bVal.localeCompare(aVal);
        
        case 'importe':
          aVal = parseFloat(a.amount || 0);
          bVal = parseFloat(b.amount || 0);
          return sortDirToUse === 'asc' ? aVal - bVal : bVal - aVal;
        
        case 'estado':
          const aStatus = this.getOrderStatus(a);
          const bStatus = this.getOrderStatus(b);
          const statusOrder = { 'pagado': 1, 'pendiente': 2 };
          aVal = statusOrder[aStatus] || 0;
          bVal = statusOrder[bStatus] || 0;
          return sortDirToUse === 'asc' ? aVal - bVal : bVal - aVal;
        
        case 'pendiente':
          aVal = this.getPendingAmount(a);
          bVal = this.getPendingAmount(b);
          return sortDirToUse === 'asc' ? aVal - bVal : bVal - aVal;
        
        case 'pago':
          // Obtener la primera forma de pago o la forma de pago antigua
          const getPaymentMethod = (order) => {
            if (order.payments && Array.isArray(order.payments) && order.payments.length > 0) {
              return order.payments[0].method || '';
            }
            return order.paymentMethod || '';
          };
          const paymentLabels = {
            'cash': 'Efectivo',
            'bank': 'Banco',
            'transfer': 'Transferencia',
            'card': 'Tarjeta'
          };
          aVal = paymentLabels[getPaymentMethod(a)] || getPaymentMethod(a);
          bVal = paymentLabels[getPaymentMethod(b)] || getPaymentMethod(b);
          return sortDirToUse === 'asc' 
            ? aVal.localeCompare(bVal)
            : bVal.localeCompare(aVal);
        
        default:
          return 0;
      }
    });

    return filtered;
  }

  renderOrdersTable() {
    const tbody = document.getElementById('ordersTbody');
    if (!tbody) return;

    // Filtrar y ordenar pedidos
    const filteredOrders = this.filterAndSortOrders(this.orders);

    // Actualizar indicadores de ordenación
    const ordersView = document.getElementById('viewMiramiraPedidos');
    if (ordersView) {
      const table = ordersView.querySelector('table thead');
      if (table) {
        const sortField = window.sortOrdersField || 'fecha';
        const sortDir = window.sortOrdersDir !== undefined ? window.sortOrdersDir : 'desc';
        table.querySelectorAll('th[data-sort]').forEach((header) => {
          if (header.dataset.sort === sortField) {
            header.dataset.sortDir = sortDir;
            const indicator = header.querySelector('[data-sort-indicator]');
            if (indicator) indicator.textContent = sortDir === 'desc' ? '↓' : '↑';
          } else {
            header.dataset.sortDir = '';
            const indicator = header.querySelector('[data-sort-indicator]');
            if (indicator) indicator.textContent = '';
          }
        });
      }
    }

    if (filteredOrders.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td class="px-3 py-6 text-center text-slate-500" colspan="10">
            ${this.orders.length === 0 
              ? `No hay pedidos registrados. ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('orders.main.create') ? 'Haz clic en "Añadir pedido" para comenzar.' : ''}`
              : 'No hay pedidos que coincidan con los filtros seleccionados.'}
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = '';
    filteredOrders.forEach(order => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50';
      
      const storeName = order.storeId 
        ? (typeof STORES !== 'undefined' ? STORES.find(s => s.id === order.storeId)?.name || order.storeId : order.storeId)
        : '—';
      
      const conceptLabels = {
        'pedido': 'Pedido',
        'royalty': 'Royalty',
        'rectificacion': 'Rectificación',
        'tara': 'Tara'
      };

      const paymentMethodLabels = {
        'cash': 'Efectivo',
        'bank': 'Banco',
        'transfer': 'Transferencia',
        'card': 'Tarjeta'
      };

      // Obtener forma de pago (primera forma de pago o forma de pago antigua)
      const getPaymentMethod = (order) => {
        if (order.payments && Array.isArray(order.payments) && order.payments.length > 0) {
          return order.payments[0].method || '';
        }
        return order.paymentMethod || '';
      };
      const paymentMethod = getPaymentMethod(order);
      const paymentMethodLabel = paymentMethod ? (paymentMethodLabels[paymentMethod] || paymentMethod) : '—';

      const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('edit');
      const canDelete = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('orders.main.delete');
      const canView = true; // Todos pueden visualizar

      // Calcular estado y pendiente
      const status = this.getOrderStatus(order);
      const pendingAmount = this.getPendingAmount(order);
      const statusLabel = status === 'pagado' ? 'Pagado' : 'Pendiente';
      const statusClass = status === 'pagado' 
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' 
        : 'bg-amber-50 text-amber-700 ring-amber-100';

      tr.innerHTML = `
        <td class="whitespace-nowrap px-3 py-3">
          <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${statusClass} ring-1">
            ${this.escapeHtml(statusLabel)}
          </span>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${order.date || '—'}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${this.escapeHtml(storeName)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${this.escapeHtml(order.invoiceNumber || '—')}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${this.escapeHtml(order.orderNumber || '—')}</td>
        <td class="whitespace-nowrap px-3 py-3">
          <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
            ${this.escapeHtml(conceptLabels[order.concept] || order.concept || '—')}
          </span>
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900">${this.formatEuro(order.amount || 0)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold ${pendingAmount > 0 ? 'text-amber-700' : 'text-emerald-700'}">${this.formatEuro(pendingAmount)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-600">${this.escapeHtml(paymentMethodLabel)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-right">
          ${canView ? `
            <button data-action="view" data-id="${order.id}" class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 ring-1 ring-transparent hover:ring-slate-100">
              Visualizar
            </button>
          ` : ''}
          ${canEdit ? `
            <button data-action="edit" data-id="${order.id}" class="ml-2 rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100">
              Editar
            </button>
          ` : ''}
          ${canDelete ? `
            <button data-action="delete" data-id="${order.id}" class="ml-2 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100">
              Eliminar
            </button>
          ` : ''}
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  initializeEventListeners() {
    // Botón añadir pedido
    const addBtn = document.getElementById('addOrderBtn');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        this.openOrderModal();
      });
    }

    // Filtro por tienda
    const filterSelect = document.getElementById('filterOrdersTienda');
    if (filterSelect) {
      filterSelect.addEventListener('change', () => {
        this.renderOrdersTable();
      });
    }

    // Filtro por período
    const periodoSelect = document.getElementById('filterOrdersPeriodo');
    if (periodoSelect) {
      periodoSelect.addEventListener('change', () => {
        const customDates = document.getElementById('filterOrdersCustomDates');
        if (customDates) {
          if (periodoSelect.value === 'custom') {
            customDates.classList.remove('hidden');
            customDates.classList.add('grid');
          } else {
            customDates.classList.add('hidden');
            customDates.classList.remove('grid');
          }
        }
        this.renderOrdersTable();
      });
    }

    // Filtros de fecha personalizada
    const fechaDesde = document.getElementById('filterOrdersFechaDesde');
    const fechaHasta = document.getElementById('filterOrdersFechaHasta');
    if (fechaDesde) {
      fechaDesde.addEventListener('change', () => {
        this.renderOrdersTable();
      });
    }
    if (fechaHasta) {
      fechaHasta.addEventListener('change', () => {
        this.renderOrdersTable();
      });
    }

    // Filtro por forma de pago
    const paymentMethodSelect = document.getElementById('filterOrdersPaymentMethod');
    if (paymentMethodSelect) {
      paymentMethodSelect.addEventListener('change', () => {
        this.renderOrdersTable();
      });
    }

    // Ordenación por clic en encabezados
    const ordersView = document.getElementById('viewMiramiraPedidos');
    if (ordersView) {
      ordersView.addEventListener('click', (e) => {
        // Buscar el th más cercano, incluso si se hace clic en el span
        let th = e.target.closest('th[data-sort]');
        // Si no se encuentra, buscar si el target es un span dentro de un th
        if (!th && e.target.tagName === 'SPAN') {
          th = e.target.parentElement.closest('th[data-sort]');
        }
        if (!th || !th.dataset.sort) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const sortField = th.dataset.sort;
        
        // Determinar la nueva dirección de ordenación
        let newDir;
        if (window.sortOrdersField === sortField) {
          // Misma columna: alternar entre desc y asc
          newDir = window.sortOrdersDir === 'desc' ? 'asc' : 'desc';
        } else {
          // Nueva columna: empezar con desc
          newDir = 'desc';
        }
        
        // Actualizar el estado global
        window.sortOrdersField = sortField;
        window.sortOrdersDir = newDir;
        
        // Renderizar (esto actualizará los indicadores)
        this.renderOrdersTable();
      });
    }

    // Delegación de eventos para acciones de la tabla
    const tbody = document.getElementById('ordersTbody');
    if (tbody) {
      tbody.addEventListener('click', (e) => {
        const button = e.target.closest('button[data-action]');
        if (!button) return;

        const action = button.getAttribute('data-action');
        const orderId = button.getAttribute('data-id');

        if (action === 'view') {
          const order = this.orders.find(o => o.id === orderId);
          if (order) {
            this.viewOrder(order);
          }
        } else if (action === 'edit') {
          const order = this.orders.find(o => o.id === orderId);
          if (order) {
            this.openOrderModal(order);
          }
        } else if (action === 'delete') {
          if (confirm('¿Estás seguro de que quieres eliminar este pedido?')) {
            this.deleteOrder(orderId);
          }
        }
      });
    }
  }

  openOrderModal(order = null) {
    const isEdit = !!order;
    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('edit');

    // Eliminar modal anterior si existe
    const existingModal = document.getElementById('orderModal');
    if (existingModal) {
      existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'orderModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4';
    modal.innerHTML = `
      <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-soft max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-base font-semibold">${isEdit ? 'Editar pedido' : 'Añadir pedido'}</div>
            <div class="text-sm text-slate-500">${isEdit ? 'Modifica los datos del pedido' : 'Completa todos los datos del nuevo pedido'}</div>
          </div>
          <button
            id="closeOrderModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form id="orderForm" class="space-y-6">
          <input type="hidden" id="orderId" value="${order?.id || ''}" />

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Fecha *</span>
              <input
                id="orderDate"
                type="date"
                required
                value="${order?.date || new Date().toISOString().split('T')[0]}"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              />
            </label>

            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Tienda *</span>
              <select
                id="orderStoreId"
                required
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              >
                <option value="">Selecciona una tienda</option>
                ${STORES.map(store => `
                  <option value="${store.id}" ${order?.storeId === store.id ? 'selected' : ''}>${this.escapeHtml(store.name)}</option>
                `).join('')}
              </select>
            </label>

            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Número de factura *</span>
              <input
                id="orderInvoiceNumber"
                type="text"
                required
                value="${this.escapeHtml(order?.invoiceNumber || '')}"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                placeholder="Ej: FACT-2025-001"
              />
            </label>

            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Número de pedido *</span>
              <input
                id="orderOrderNumber"
                type="text"
                required
                value="${this.escapeHtml(order?.orderNumber || '')}"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                placeholder="Ej: PED-2025-001"
              />
            </label>

            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Concepto *</span>
              <select
                id="orderConcept"
                required
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              >
                <option value="">Selecciona un concepto</option>
                <option value="pedido" ${order?.concept === 'pedido' ? 'selected' : ''}>Pedido</option>
                <option value="royalty" ${order?.concept === 'royalty' ? 'selected' : ''}>Royalty</option>
                <option value="rectificacion" ${order?.concept === 'rectificacion' ? 'selected' : ''}>Rectificación</option>
                <option value="tara" ${order?.concept === 'tara' ? 'selected' : ''}>Tara</option>
              </select>
            </label>

            <label class="block">
              <span class="text-xs font-semibold text-slate-700">Importe (€) *</span>
              <input
                id="orderAmount"
                type="number"
                inputmode="decimal"
                step="0.01"
                min="0"
                required
                value="${order?.amount || ''}"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
                placeholder="0.00"
              />
            </label>

          </div>

          <!-- Sección de Pagos -->
          <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
            <div class="mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-emerald-700">
                  <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="text-sm font-semibold text-emerald-900">Pagos</span>
              </div>
              <button
                type="button"
                id="addPaymentBtn"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-brand-200"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Añadir pago
              </button>
            </div>

            <div id="paymentsContainer" class="space-y-3">
              <!-- Los pagos se añadirán aquí dinámicamente -->
            </div>

            <div class="mt-4 rounded-xl bg-white p-3 ring-1 ring-slate-100">
              <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-slate-700">Total pagado:</span>
                <span id="totalPaidDisplay" class="font-semibold text-emerald-700">0,00 €</span>
              </div>
              <div class="mt-2 flex items-center justify-between text-sm">
                <span class="font-semibold text-slate-700">Importe del pedido:</span>
                <span id="orderAmountDisplay" class="font-semibold text-slate-900">0,00 €</span>
              </div>
              <div class="mt-2 flex items-center justify-between text-sm border-t border-slate-200 pt-2">
                <span class="font-semibold text-slate-700">Pendiente:</span>
                <span id="pendingAmountDisplay" class="font-semibold text-amber-700">0,00 €</span>
              </div>
            </div>
          </div>

          ${isEdit && order ? `
            <!-- Historial de cambios -->
            <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-4 ring-1 ring-slate-200">
              <div class="mb-3 flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-700">
                  <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="text-sm font-semibold text-slate-900">Historial de cambios</span>
              </div>
              <div class="space-y-2 text-xs" id="orderHistoryContent">
                ${this.renderOrderHistory(order)}
              </div>
            </div>
          ` : ''}

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
            <button
              type="button"
              id="cancelOrderBtn"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            >
              ${isEdit ? 'Guardar cambios' : 'Crear pedido'}
            </button>
          </div>
        </form>
      </div>
    `;

    document.body.appendChild(modal);

    // Inicializar pagos existentes o crear uno vacío
    this.initializePayments(order);

    // Event listeners
    document.getElementById('closeOrderModalBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('cancelOrderBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('addPaymentBtn').addEventListener('click', () => {
      this.addPaymentRow();
    });

    // Actualizar totales cuando cambia el importe del pedido
    document.getElementById('orderAmount').addEventListener('input', () => {
      this.updatePaymentTotals();
    });

    document.getElementById('orderForm').addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleFormSubmit(order?.id);
    });
  }

  initializePayments(order) {
    const container = document.getElementById('paymentsContainer');
    if (!container) return;

    container.innerHTML = '';

    // Si hay un pedido existente con pagos, cargarlos
    if (order && order.payments && Array.isArray(order.payments) && order.payments.length > 0) {
      order.payments.forEach((payment, index) => {
        this.addPaymentRow(payment, index);
      });
    } else if (order && order.paymentAmount && order.paymentDate && order.paymentMethod) {
      // Compatibilidad con datos antiguos: convertir pago único a array
      this.addPaymentRow({
        amount: order.paymentAmount,
        date: order.paymentDate,
        method: order.paymentMethod
      }, 0);
    } else {
      // Si no hay pagos, no añadir ninguno por defecto
    }

    this.updatePaymentTotals();
  }

  addPaymentRow(payment = null, index = null) {
    const container = document.getElementById('paymentsContainer');
    if (!container) return;

    const paymentIndex = index !== null ? index : container.children.length;
    const paymentId = `payment-${paymentIndex}`;

    const row = document.createElement('div');
    const isSaved = payment && payment.date && payment.method && payment.amount;
    row.className = `rounded-xl border p-3 ring-1 ${isSaved ? 'border-emerald-200 bg-emerald-50/30 ring-emerald-100' : 'border-slate-200 bg-white ring-slate-100'}`;
    row.dataset.paymentIndex = paymentIndex;
    row.dataset.paymentSaved = isSaved ? 'true' : 'false';
    row.innerHTML = `
      <div class="space-y-3">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Fecha de pago <span class="text-rose-600">*</span></span>
            <input
              type="date"
              class="payment-date mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              value="${payment?.date || ''}"
              ${isSaved ? 'readonly' : ''}
            />
          </label>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Forma de pago <span class="text-rose-600">*</span></span>
            <select
              class="payment-method mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              ${isSaved ? 'disabled' : ''}
            >
              <option value="">Selecciona...</option>
              <option value="cash" ${payment?.method === 'cash' ? 'selected' : ''}>Efectivo</option>
              <option value="bank" ${payment?.method === 'bank' ? 'selected' : ''}>Banco</option>
              <option value="transfer" ${payment?.method === 'transfer' ? 'selected' : ''}>Transferencia</option>
              <option value="card" ${payment?.method === 'card' ? 'selected' : ''}>Tarjeta</option>
            </select>
          </label>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Importe (€) <span class="text-rose-600">*</span></span>
            <input
              type="number"
              inputmode="decimal"
              step="0.01"
              min="0"
              class="payment-amount mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
              placeholder="0.00"
              value="${payment?.amount || ''}"
              ${isSaved ? 'readonly' : ''}
            />
          </label>
          <div class="flex items-end gap-2">
            ${!isSaved ? `
              <button
                type="button"
                class="save-payment-btn flex-1 rounded-xl bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200"
              >
                Guardar
              </button>
            ` : `
              <div class="flex-1 rounded-xl bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 text-center">
                ✓ Guardado
              </div>
            `}
            <button
              type="button"
              class="remove-payment-btn rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-200"
            >
              Eliminar
            </button>
          </div>
        </div>
        <div class="payment-error hidden rounded-lg bg-rose-50 p-2 text-xs text-rose-700 ring-1 ring-rose-200"></div>
      </div>
    `;

    container.appendChild(row);

    // Event listeners para este pago
    const removeBtn = row.querySelector('.remove-payment-btn');
    if (removeBtn) {
      removeBtn.addEventListener('click', () => {
        // Registrar eliminación en historial si el pago estaba guardado
        const isSaved = row.dataset.paymentSaved === 'true';
        const orderIdInput = document.getElementById('orderId');
        const orderId = orderIdInput ? orderIdInput.value : null;
        
        if (isSaved && orderId) {
          const order = this.orders.find(o => o.id === orderId);
          if (order) {
            const date = row.querySelector('.payment-date')?.value || '';
            const method = row.querySelector('.payment-method')?.value || '';
            const amount = parseFloat(row.querySelector('.payment-amount')?.value || 0);
            
            this.addHistoryEntry(order, 'payment_removed', {
              payment: {
                date: date,
                method: method,
                amount: amount
              }
            });
            this.saveOrders();
          }
        }
        
        row.remove();
        this.updatePaymentTotals();
      });
    }

    const saveBtn = row.querySelector('.save-payment-btn');
    if (saveBtn) {
      // Obtener el ID del pedido si estamos editando
      const orderIdInput = document.getElementById('orderId');
      const orderId = orderIdInput ? orderIdInput.value : null;
      saveBtn.addEventListener('click', () => {
        this.validateAndSavePayment(row, orderId);
      });
    }

    const amountInput = row.querySelector('.payment-amount');
    if (amountInput) {
      amountInput.addEventListener('input', () => {
        this.updatePaymentTotals();
        // Si el pago estaba guardado, marcarlo como no guardado al cambiar
        if (row.dataset.paymentSaved === 'true') {
          this.markPaymentAsUnsaved(row);
        }
      });
    }

    const dateInput = row.querySelector('.payment-date');
    if (dateInput) {
      dateInput.addEventListener('change', () => {
        if (row.dataset.paymentSaved === 'true') {
          this.markPaymentAsUnsaved(row);
        }
      });
    }

    const methodSelect = row.querySelector('.payment-method');
    if (methodSelect) {
      methodSelect.addEventListener('change', () => {
        if (row.dataset.paymentSaved === 'true') {
          this.markPaymentAsUnsaved(row);
        }
      });
    }

    this.updatePaymentTotals();
  }

  validateAndSavePayment(row, orderId = null) {
    const dateInput = row.querySelector('.payment-date');
    const methodSelect = row.querySelector('.payment-method');
    const amountInput = row.querySelector('.payment-amount');
    const errorDiv = row.querySelector('.payment-error');

    if (!dateInput || !methodSelect || !amountInput || !errorDiv) return;

    const date = dateInput.value.trim();
    const method = methodSelect.value;
    const amount = parseFloat(amountInput.value) || 0;

    // Validaciones
    let errors = [];
    if (!date) {
      errors.push('La fecha de pago es obligatoria');
    }
    if (!method) {
      errors.push('La forma de pago es obligatoria');
    }
    if (!amount || amount <= 0) {
      errors.push('El importe debe ser mayor a 0');
    }

    // Validar que el total de pagos no exceda el importe del pedido
    const orderAmountInput = document.getElementById('orderAmount');
    if (orderAmountInput) {
      const orderAmount = parseFloat(orderAmountInput.value) || 0;
      if (orderAmount > 0) {
        // Calcular total de pagos guardados + este pago
        const container = document.getElementById('paymentsContainer');
        let totalPaid = 0;
        if (container) {
          container.querySelectorAll('[data-payment-saved="true"]').forEach(savedRow => {
            const savedAmount = parseFloat(savedRow.querySelector('.payment-amount')?.value || 0);
            totalPaid += savedAmount;
          });
        }
        // Añadir el importe de este pago
        totalPaid += amount;
        if (totalPaid > orderAmount) {
          errors.push(`El total de pagos (${this.formatEuro(totalPaid)}) no puede exceder el importe del pedido (${this.formatEuro(orderAmount)})`);
        }
      }
    }

    if (errors.length > 0) {
      errorDiv.textContent = errors.join('. ');
      errorDiv.classList.remove('hidden');
      return false;
    }

    // Si todo está bien, marcar como guardado
    errorDiv.classList.add('hidden');
    this.markPaymentAsSaved(row);
    this.updatePaymentTotals();
    return true;
  }

  markPaymentAsSaved(row) {
    row.dataset.paymentSaved = 'true';
    row.className = 'rounded-xl border border-emerald-200 bg-emerald-50/30 p-3 ring-1 ring-emerald-100';
    
    const dateInput = row.querySelector('.payment-date');
    const methodSelect = row.querySelector('.payment-method');
    const amountInput = row.querySelector('.payment-amount');
    const saveBtn = row.querySelector('.save-payment-btn');
    const errorDiv = row.querySelector('.payment-error');

    if (dateInput) dateInput.setAttribute('readonly', 'readonly');
    if (methodSelect) methodSelect.setAttribute('disabled', 'disabled');
    if (amountInput) amountInput.setAttribute('readonly', 'readonly');
    if (errorDiv) errorDiv.classList.add('hidden');

    // Reemplazar botón guardar con indicador guardado
    if (saveBtn) {
      const savedIndicator = document.createElement('div');
      savedIndicator.className = 'flex-1 rounded-xl bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 text-center';
      savedIndicator.textContent = '✓ Guardado';
      saveBtn.parentNode.replaceChild(savedIndicator, saveBtn);
    }
  }

  markPaymentAsUnsaved(row) {
    // Guardar los valores antiguos antes de marcar como no guardado
    const oldDate = row.querySelector('.payment-date')?.value || '';
    const oldMethod = row.querySelector('.payment-method')?.value || '';
    const oldAmount = parseFloat(row.querySelector('.payment-amount')?.value || 0);
    
    row.dataset.paymentSaved = 'false';
    row.className = 'rounded-xl border border-slate-200 bg-white p-3 ring-1 ring-slate-100';
    
    const dateInput = row.querySelector('.payment-date');
    const methodSelect = row.querySelector('.payment-method');
    const amountInput = row.querySelector('.payment-amount');
    const savedIndicator = row.querySelector('.flex-1.rounded-xl.bg-emerald-100');

    if (dateInput) dateInput.removeAttribute('readonly');
    if (methodSelect) methodSelect.removeAttribute('disabled');
    if (amountInput) amountInput.removeAttribute('readonly');

    // Reemplazar indicador guardado con botón guardar
    if (savedIndicator) {
      const saveBtn = document.createElement('button');
      saveBtn.type = 'button';
      saveBtn.className = 'save-payment-btn flex-1 rounded-xl bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200';
      saveBtn.textContent = 'Guardar';
      
      // Obtener el ID del pedido si estamos editando
      const orderIdInput = document.getElementById('orderId');
      const orderId = orderIdInput ? orderIdInput.value : null;
      
      // Guardar valores antiguos en el botón para poder comparar después
      saveBtn.dataset.oldDate = oldDate;
      saveBtn.dataset.oldMethod = oldMethod;
      saveBtn.dataset.oldAmount = oldAmount.toString();
      
      saveBtn.addEventListener('click', () => {
        // Comparar valores antiguos con nuevos para registrar modificación
        const newDate = row.querySelector('.payment-date')?.value || '';
        const newMethod = row.querySelector('.payment-method')?.value || '';
        const newAmount = parseFloat(row.querySelector('.payment-amount')?.value || 0);
        
        if (orderId && (oldDate !== newDate || oldMethod !== newMethod || oldAmount !== newAmount)) {
          const order = this.orders.find(o => o.id === orderId);
          if (order) {
            this.addHistoryEntry(order, 'payment_modified', {
              payment: {
                old: { date: oldDate, method: oldMethod, amount: oldAmount },
                new: { date: newDate, method: newMethod, amount: newAmount }
              }
            });
            this.saveOrders();
          }
        }
        
        this.validateAndSavePayment(row, orderId);
      });
      
      savedIndicator.parentNode.replaceChild(saveBtn, savedIndicator);
    }
  }

  updatePaymentTotals() {
    const container = document.getElementById('paymentsContainer');
    const orderAmountInput = document.getElementById('orderAmount');
    const totalPaidDisplay = document.getElementById('totalPaidDisplay');
    const orderAmountDisplay = document.getElementById('orderAmountDisplay');
    const pendingAmountDisplay = document.getElementById('pendingAmountDisplay');

    if (!container || !orderAmountInput || !totalPaidDisplay || !orderAmountDisplay || !pendingAmountDisplay) return;

    // Calcular total pagado (solo pagos guardados)
    let totalPaid = 0;
    container.querySelectorAll('[data-payment-saved="true"]').forEach(row => {
      const amount = parseFloat(row.querySelector('.payment-amount')?.value || 0);
      totalPaid += amount;
    });

    const orderAmount = parseFloat(orderAmountInput.value) || 0;
    const pendingAmount = Math.max(0, orderAmount - totalPaid);

    totalPaidDisplay.textContent = this.formatEuro(totalPaid);
    orderAmountDisplay.textContent = this.formatEuro(orderAmount);
    pendingAmountDisplay.textContent = this.formatEuro(pendingAmount);

    // Cambiar color del pendiente según si hay pendiente o no
    if (pendingAmount > 0) {
      pendingAmountDisplay.className = 'font-semibold text-amber-700';
    } else {
      pendingAmountDisplay.className = 'font-semibold text-emerald-700';
    }
  }

  // Obtener usuario actual
  getCurrentUser() {
    if (typeof authManager !== 'undefined' && authManager && authManager.isAuthenticated && authManager.isAuthenticated()) {
      const currentUser = authManager.getCurrentUser();
      if (currentUser) {
        return {
          id: currentUser.id || currentUser.username,
          name: currentUser.name || currentUser.username || 'Usuario'
        };
      }
    }
    return { id: 'system', name: 'Sistema' };
  }

  // Registrar cambio en el historial
  addHistoryEntry(order, action, changes = null) {
    if (!order.history) {
      order.history = [];
    }
    
    const user = this.getCurrentUser();
    const historyEntry = {
      action: action,
      userId: user.id,
      userName: user.name,
      timestamp: Date.now(),
      changes: changes || undefined
    };
    
    order.history.push(historyEntry);
  }

  // Formatear fecha y hora para el historial
  formatDateTime(timestamp) {
    const date = new Date(timestamp);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  }

  handleFormSubmit(orderId) {
    const formData = {
      date: document.getElementById('orderDate').value,
      storeId: document.getElementById('orderStoreId').value,
      invoiceNumber: document.getElementById('orderInvoiceNumber').value.trim(),
      orderNumber: document.getElementById('orderOrderNumber').value.trim(),
      concept: document.getElementById('orderConcept').value,
      amount: parseFloat(document.getElementById('orderAmount').value) || 0,
    };

    // Validaciones
    if (!formData.date) {
      alert('La fecha es obligatoria.');
      return;
    }
    if (!formData.storeId) {
      alert('La tienda es obligatoria.');
      return;
    }
    if (!formData.invoiceNumber) {
      alert('El número de factura es obligatorio.');
      return;
    }
    if (!formData.orderNumber) {
      alert('El número de pedido es obligatorio.');
      return;
    }
    if (!formData.concept) {
      alert('El concepto es obligatorio.');
      return;
    }
    if (!formData.amount || formData.amount <= 0) {
      alert('El importe es obligatorio y debe ser mayor a 0.');
      return;
    }

    // Recopilar pagos (solo los que están guardados/validados)
    const payments = [];
    const container = document.getElementById('paymentsContainer');
    let hasUnsavedPayments = false;
    
    if (container) {
      container.querySelectorAll('[data-payment-index]').forEach(row => {
        const isSaved = row.dataset.paymentSaved === 'true';
        const date = row.querySelector('.payment-date')?.value || '';
        const method = row.querySelector('.payment-method')?.value || '';
        const amount = parseFloat(row.querySelector('.payment-amount')?.value || 0);

        // Si el pago tiene datos pero no está guardado, marcar como pendiente
        if ((date || method || amount > 0) && !isSaved) {
          hasUnsavedPayments = true;
        }

        // Solo añadir pagos guardados con datos válidos
        if (isSaved && date && method && amount > 0) {
          payments.push({
            date: date,
            method: method,
            amount: amount
          });
        }
      });
    }

    // Si hay pagos sin guardar, mostrar advertencia
    if (hasUnsavedPayments) {
      if (!confirm('Hay pagos sin guardar. ¿Deseas guardar el pedido sin incluir esos pagos? Los pagos no guardados se perderán.')) {
        return;
      }
    }

    // Validar que el total de pagos no exceda el importe del pedido
    const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
    if (totalPaid > formData.amount) {
      alert(`El total de pagos (${this.formatEuro(totalPaid)}) no puede exceder el importe del pedido (${this.formatEuro(formData.amount)}).`);
      return;
    }

    if (orderId) {
      // Editar
      const index = this.orders.findIndex(o => o.id === orderId);
      if (index === -1) {
        alert('Pedido no encontrado.');
        return;
      }
      
      const oldOrder = this.orders[index];
      const changes = {};
      
      // Comparar campos para detectar cambios
      const fieldsToTrack = ['date', 'storeId', 'invoiceNumber', 'orderNumber', 'concept', 'amount'];
      fieldsToTrack.forEach(field => {
        if (oldOrder[field] !== formData[field]) {
          changes[field] = {
            old: oldOrder[field],
            new: formData[field]
          };
        }
      });
      
      // Comparar pagos
      const oldPayments = oldOrder.payments || [];
      if (JSON.stringify(oldPayments) !== JSON.stringify(payments)) {
        changes.payments = {
          old: oldPayments.length,
          new: payments.length
        };
      }
      
      this.orders[index] = {
        ...oldOrder,
        ...formData,
        payments: payments.length > 0 ? payments : [],
        // Mantener compatibilidad: eliminar campos antiguos si existen
        paymentMethod: undefined,
        paymentDate: undefined,
        paymentAmount: undefined,
        updatedAt: new Date().toISOString()
      };
      
      // Registrar en historial
      this.addHistoryEntry(this.orders[index], 'updated', Object.keys(changes).length > 0 ? changes : null);
    } else {
      // Crear
      const newOrder = {
        id: 'order-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
        ...formData,
        payments: payments.length > 0 ? payments : [],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
        history: []
      };
      
      // Registrar creación en historial
      this.addHistoryEntry(newOrder, 'created');
      
      this.orders.push(newOrder);
    }

    this.saveOrders();
    document.getElementById('orderModal').remove();
    this.renderOrdersSummary();
    this.renderOrdersTable();
  }

  viewOrder(order) {
    // Eliminar modal anterior si existe
    const existingModal = document.getElementById('orderViewModal');
    if (existingModal) {
      existingModal.remove();
    }

    const storeName = order.storeId 
      ? (typeof STORES !== 'undefined' ? STORES.find(s => s.id === order.storeId)?.name || order.storeId : order.storeId)
      : '—';

    const conceptLabels = {
      'pedido': 'Pedido',
      'royalty': 'Royalty',
      'rectificacion': 'Rectificación',
      'tara': 'Tara'
    };

    const paymentMethodLabels = {
      'cash': 'Efectivo',
      'bank': 'Banco',
      'transfer': 'Transferencia',
      'card': 'Tarjeta'
    };

    const status = this.getOrderStatus(order);
    const totalPaid = this.getTotalPaid(order);
    const pendingAmount = this.getPendingAmount(order);

    // Generar lista de pagos
    let paymentsHtml = '';
    if (order.payments && Array.isArray(order.payments) && order.payments.length > 0) {
      order.payments.forEach((payment, index) => {
        paymentsHtml += `
          <tr class="border-b border-slate-100">
            <td class="px-3 py-2 text-sm text-slate-600">${payment.date || '—'}</td>
            <td class="px-3 py-2 text-sm text-slate-600">${paymentMethodLabels[payment.method] || payment.method || '—'}</td>
            <td class="px-3 py-2 text-sm text-right font-semibold text-slate-900">${this.formatEuro(payment.amount || 0)}</td>
          </tr>
        `;
      });
    } else if (order.paymentAmount && order.paymentDate && order.paymentMethod) {
      // Compatibilidad con datos antiguos
      paymentsHtml += `
        <tr class="border-b border-slate-100">
          <td class="px-3 py-2 text-sm text-slate-600">${order.paymentDate}</td>
          <td class="px-3 py-2 text-sm text-slate-600">${paymentMethodLabels[order.paymentMethod] || order.paymentMethod}</td>
          <td class="px-3 py-2 text-sm text-right font-semibold text-slate-900">${this.formatEuro(order.paymentAmount)}</td>
        </tr>
      `;
    } else {
      paymentsHtml = '<tr><td colspan="3" class="px-3 py-4 text-center text-slate-500 text-sm">No hay pagos registrados</td></tr>';
    }

    const modal = document.createElement('div');
    modal.id = 'orderViewModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4';
    modal.innerHTML = `
      <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-soft max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-base font-semibold">Detalles del pedido</div>
            <div class="text-sm text-slate-500">Información completa del pedido</div>
          </div>
          <button
            id="closeOrderViewModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <span class="text-xs font-semibold text-slate-700">Fecha</span>
              <div class="mt-1 text-sm text-slate-900">${order.date || '—'}</div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Tienda</span>
              <div class="mt-1 text-sm text-slate-900">${this.escapeHtml(storeName)}</div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Número de factura</span>
              <div class="mt-1 text-sm text-slate-900">${this.escapeHtml(order.invoiceNumber || '—')}</div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Número de pedido</span>
              <div class="mt-1 text-sm text-slate-900">${this.escapeHtml(order.orderNumber || '—')}</div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Concepto</span>
              <div class="mt-1">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                  ${this.escapeHtml(conceptLabels[order.concept] || order.concept || '—')}
                </span>
              </div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Estado</span>
              <div class="mt-1">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${status === 'pagado' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100'} ring-1">
                  ${status === 'pagado' ? 'Pagado' : 'Pendiente'}
                </span>
              </div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Importe total</span>
              <div class="mt-1 text-sm font-semibold text-slate-900">${this.formatEuro(order.amount || 0)}</div>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-700">Importe pendiente</span>
              <div class="mt-1 text-sm font-semibold ${pendingAmount > 0 ? 'text-amber-700' : 'text-emerald-700'}">${this.formatEuro(pendingAmount)}</div>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-900 mb-3">Pagos registrados</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                  <tr>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-left">Forma de pago</th>
                    <th class="px-3 py-2 text-right">Importe</th>
                  </tr>
                </thead>
                <tbody>
                  ${paymentsHtml}
                </tbody>
                <tfoot class="border-t-2 border-slate-200 bg-white">
                  <tr>
                    <td colspan="2" class="px-3 py-2 text-sm font-semibold text-slate-900">Total pagado:</td>
                    <td class="px-3 py-2 text-sm text-right font-semibold text-emerald-700">${this.formatEuro(totalPaid)}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Historial de cambios -->
          <div class="rounded-xl border-2 border-slate-200 bg-slate-50/50 p-4 ring-1 ring-slate-200">
            <div class="mb-3 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-700">
                <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
              </svg>
              <span class="text-sm font-semibold text-slate-900">Historial de cambios</span>
            </div>
            <div class="space-y-2 text-xs">
              ${this.renderOrderHistory(order)}
            </div>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
          <button
            type="button"
            id="closeOrderViewModalBtn2"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Cerrar
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Event listeners
    document.getElementById('closeOrderViewModalBtn').addEventListener('click', () => {
      modal.remove();
    });
    document.getElementById('closeOrderViewModalBtn2').addEventListener('click', () => {
      modal.remove();
    });
  }

  deleteOrder(orderId) {
    const index = this.orders.findIndex(o => o.id === orderId);
    if (index === -1) {
      alert('Pedido no encontrado.');
      return;
    }
    
    // Registrar eliminación en historial antes de eliminar
    const order = this.orders[index];
    this.addHistoryEntry(order, 'deleted');
    this.saveOrders();
    
    this.orders.splice(index, 1);
    this.saveOrders();
    this.renderOrdersSummary();
    this.renderOrdersTable();
  }

  renderOrderHistory(order) {
    if (!order || !order.history || order.history.length === 0) {
      return '<div class="text-xs text-slate-500">No hay historial de cambios</div>';
    }

    const fieldLabels = {
      date: 'Fecha',
      storeId: 'Tienda',
      invoiceNumber: 'Número de factura',
      orderNumber: 'Número de pedido',
      concept: 'Concepto',
      amount: 'Importe',
      payments: 'Pagos'
    };

    const actionLabels = {
      created: 'Creado',
      updated: 'Modificado',
      payment_added: 'Pago añadido',
      payment_removed: 'Pago eliminado',
      payment_modified: 'Pago modificado',
      deleted: 'Eliminado'
    };

    // Ordenar por fecha (más reciente primero)
    const sortedHistory = [...order.history].sort((a, b) => {
      const timestampA = typeof a.timestamp === 'string' ? new Date(a.timestamp).getTime() : a.timestamp;
      const timestampB = typeof b.timestamp === 'string' ? new Date(b.timestamp).getTime() : b.timestamp;
      return timestampB - timestampA;
    });

    let html = '';
    sortedHistory.forEach((item) => {
      const actionLabel = actionLabels[item.action] || item.action;
      const timestamp = typeof item.timestamp === 'string' 
        ? new Date(item.timestamp).getTime() 
        : item.timestamp;
      
      html += `
        <div class="rounded-lg border border-slate-200 bg-white p-3">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-900">${this.escapeHtml(actionLabel)}</span>
                <span class="text-xs text-slate-500">por ${this.escapeHtml(item.userName || 'Usuario desconocido')}</span>
              </div>
              <div class="mt-1 text-xs text-slate-500">${this.formatDateTime(timestamp)}</div>
              ${item.changes && Object.keys(item.changes).length > 0 ? `
                <div class="mt-2 space-y-1">
                  ${Object.entries(item.changes).map(([field, change]) => {
                    const label = fieldLabels[field] || field;
                    let oldVal = '—';
                    let newVal = '—';
                    
                    if (field === 'payments') {
                      oldVal = `${change.old || 0} pago(s)`;
                      newVal = `${change.new || 0} pago(s)`;
                    } else if (field === 'storeId') {
                      const oldStore = typeof STORES !== 'undefined' && change.old 
                        ? STORES.find(s => s.id === change.old)?.name || change.old 
                        : change.old;
                      const newStore = typeof STORES !== 'undefined' && change.new 
                        ? STORES.find(s => s.id === change.new)?.name || change.new 
                        : change.new;
                      oldVal = oldStore || '—';
                      newVal = newStore || '—';
                    } else if (field === 'concept') {
                      const conceptLabels = {
                        'pedido': 'Pedido',
                        'royalty': 'Royalty',
                        'rectificacion': 'Rectificación',
                        'tara': 'Tara'
                      };
                      oldVal = conceptLabels[change.old] || change.old || '—';
                      newVal = conceptLabels[change.new] || change.new || '—';
                    } else if (field === 'amount') {
                      oldVal = this.formatEuro(change.old || 0);
                      newVal = this.formatEuro(change.new || 0);
                    } else if (field === 'payment') {
                      const paymentMethodLabels = {
                        'cash': 'Efectivo',
                        'bank': 'Banco',
                        'transfer': 'Transferencia',
                        'card': 'Tarjeta'
                      };
                      oldVal = `${change.old?.date || ''} - ${paymentMethodLabels[change.old?.method] || change.old?.method || ''} - ${this.formatEuro(change.old?.amount || 0)}`;
                      newVal = `${change.new?.date || ''} - ${paymentMethodLabels[change.new?.method] || change.new?.method || ''} - ${this.formatEuro(change.new?.amount || 0)}`;
                    } else {
                      oldVal = change.old !== null && change.old !== undefined ? String(change.old) : '—';
                      newVal = change.new !== null && change.new !== undefined ? String(change.new) : '—';
                    }
                    
                    return `
                      <div class="text-xs text-slate-600">
                        <span class="font-medium">${this.escapeHtml(label)}:</span>
                        <span class="text-rose-600 line-through">${this.escapeHtml(oldVal)}</span>
                        <span class="mx-1">→</span>
                        <span class="text-emerald-600 font-medium">${this.escapeHtml(newVal)}</span>
                      </div>
                    `;
                  }).join('')}
                </div>
              ` : ''}
            </div>
          </div>
        </div>
      `;
    });

    return html;
  }

  formatEuro(amount) {
    return new Intl.NumberFormat('es-ES', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  getAllOrders() {
    return this.orders;
  }
}

// Instancia global del gestor de pedidos
// Se inicializa en app.js después de la autenticación
let orderManager;
