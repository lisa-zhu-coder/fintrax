// Gestión de empleados
const EMPLOYEES_STORAGE_KEY = 'miramira_employees';

class EmployeeManager {
  constructor() {
    this.employees = this.loadEmployees();
    this.initializeUI();
  }

  loadEmployees() {
    const stored = localStorage.getItem(EMPLOYEES_STORAGE_KEY);
    let employees = [];
    if (stored) {
      try {
        employees = JSON.parse(stored);
      } catch (e) {
        console.error('Error cargando empleados:', e);
        return [];
      }
    }
    
    // Migración: convertir address antigua a street, postalCode, city
    // Migración: convertir storeId a storeIds (array)
    employees = employees.map(employee => {
      if (employee.address && !employee.street) {
        employee.street = employee.address;
        employee.postalCode = '';
        employee.city = '';
        delete employee.address;
      }
      // Migrar storeId a storeIds (array)
      if (employee.storeId && !employee.storeIds) {
        employee.storeIds = [employee.storeId];
        delete employee.storeId;
      } else if (!employee.storeIds) {
        employee.storeIds = [];
      }
      // Asegurar que existan los campos
      if (!employee.street) employee.street = '';
      if (!employee.postalCode) employee.postalCode = '';
      if (!employee.city) employee.city = '';
      if (!employee.email) employee.email = '';
      if (!employee.payrolls) employee.payrolls = [];
      return employee;
    });
    
    return employees;
  }

  saveEmployees() {
    localStorage.setItem(EMPLOYEES_STORAGE_KEY, JSON.stringify(this.employees));
  }

  initializeUI() {
    // Crear enlace en el menú del sidebar
    this.createMenuLink();
  }

  createMenuLink() {
    const sidebar = document.querySelector('aside nav');
    if (!sidebar) return;

    // Verificar si ya existe
    if (document.getElementById('employeesMenuLink')) return;

    const employeesLink = document.createElement('a');
    employeesLink.id = 'employeesMenuLink';
    employeesLink.href = '#';
    employeesLink.className = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50';
    employeesLink.innerHTML = `
      <span class="text-slate-500" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </span>
      Empleados
    `;

    employeesLink.addEventListener('click', (e) => {
      e.preventDefault();
      this.showEmployeesView();
    });

    sidebar.appendChild(employeesLink);
  }

  showEmployeesView() {
    const main = document.querySelector('main');
    if (!main) return;

    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('hr.employees.configure');

    main.innerHTML = `
      <div class="space-y-6">
        <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-lg font-semibold">Empleados</h1>
              <p class="text-sm text-slate-500">Gestiona la información de todos los empleados de la empresa</p>
            </div>
            <div class="flex items-center gap-3">
              ${canEdit ? `
                <button
                  id="uploadPayrollsGlobalBtn"
                  class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Subir nóminas (PDF)
                </button>
                <button
                  id="addEmployeeBtn"
                  class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-200"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  Añadir empleado
                </button>
              ` : ''}
              <button
                id="csvImportExportBtn"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                CSV
              </button>
            </div>
          </div>
        </header>

        <input
          type="file"
          id="globalPayrollFileInput"
          accept=".pdf"
          multiple
          class="hidden"
        />

        <div class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead class="text-xs uppercase text-slate-500">
                <tr>
                  <th class="whitespace-nowrap px-3 py-2">Nombre completo</th>
                  <th class="whitespace-nowrap px-3 py-2">DNI</th>
                  <th class="whitespace-nowrap px-3 py-2">Puesto</th>
                  <th class="whitespace-nowrap px-3 py-2">Tienda</th>
                  <th class="whitespace-nowrap px-3 py-2">Usuario</th>
                  <th class="whitespace-nowrap px-3 py-2">Fecha inicio</th>
                  <th class="whitespace-nowrap px-3 py-2">Teléfono</th>
                  <th class="whitespace-nowrap px-3 py-2"></th>
                </tr>
              </thead>
              <tbody id="employeesTbody" class="divide-y divide-slate-100">
                <!-- rows injected -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;

    this.renderEmployeesTable();
    this.initializeEventListeners();
  }

  renderEmployeesTable() {
    const tbody = document.getElementById('employeesTbody');
    if (!tbody) return;

    if (this.employees.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td class="px-3 py-6 text-center text-slate-500" colspan="8">
            No hay empleados registrados. ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('hr.employees.configure') ? 'Haz clic en "Añadir empleado" para comenzar.' : ''}
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = '';
    this.employees.forEach(employee => {
      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50';
      
      // Mostrar todas las tiendas asignadas
      let storeName = '—';
      if (employee.storeIds && employee.storeIds.length > 0) {
        // Verificar si tiene todas las tiendas asignadas
        const allStoreIds = STORES.map(s => s.id);
        const hasAllStores = allStoreIds.length === employee.storeIds.length && 
          allStoreIds.every(id => employee.storeIds.includes(id));
        
        if (hasAllStores) {
          storeName = 'Todas las tiendas';
        } else {
          const storeNames = employee.storeIds
            .map(id => STORES.find(s => s.id === id)?.name || id)
            .filter(Boolean);
          storeName = storeNames.length > 0 ? storeNames.join(', ') : '—';
        }
      } else if (employee.storeId) {
        // Compatibilidad con datos antiguos
        storeName = STORES.find(s => s.id === employee.storeId)?.name || employee.storeId;
      }
      
      const startDate = employee.startDate || '—';
      const phone = employee.phone || '—';

      // Obtener información del usuario asociado
      let userInfo = '—';
      if (employee.userId && typeof authManager !== 'undefined' && authManager) {
        const users = authManager.getAllUsers();
        const user = users.find(u => u.id === employee.userId);
        if (user) {
          userInfo = `${user.name || user.username} (${ROLES[user.role] ? ROLES[user.role].name : user.role})`;
        }
      }

      tr.innerHTML = `
        <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-900">${this.escapeHtml(employee.fullName || '—')}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${this.escapeHtml(employee.dni || '—')}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${this.escapeHtml(employee.position || '—')}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${this.escapeHtml(storeName)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">
          ${userInfo !== '—' ? `
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
              ${this.escapeHtml(userInfo)}
            </span>
          ` : '<span class="text-slate-400">Sin usuario</span>'}
        </td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${startDate}</td>
        <td class="whitespace-nowrap px-3 py-3 text-slate-700">${this.escapeHtml(phone)}</td>
        <td class="whitespace-nowrap px-3 py-3 text-right">
          <button
            data-action="view"
            data-id="${employee.id}"
            class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100"
          >
            Ver
          </button>
          ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('hr.employees.configure') ? `
            <button
              data-action="edit"
              data-id="${employee.id}"
              class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100"
            >
              Editar
            </button>
            <button
              data-action="delete"
              data-id="${employee.id}"
              class="ml-1 rounded-lg px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100"
            >
              Borrar
            </button>
          ` : ''}
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  initializeEventListeners() {
    // Botón CSV importar/exportar
    const csvBtn = document.getElementById('csvImportExportBtn');
    if (csvBtn) {
      csvBtn.addEventListener('click', () => {
        this.showCSVMenu(csvBtn);
      });
    }

    // Botón añadir empleado
    const addBtn = document.getElementById('addEmployeeBtn');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        this.openEmployeeModal();
      });
    }

    // Botón subir nóminas global
    const uploadPayrollsGlobalBtn = document.getElementById('uploadPayrollsGlobalBtn');
    const globalPayrollFileInput = document.getElementById('globalPayrollFileInput');
    if (uploadPayrollsGlobalBtn && globalPayrollFileInput) {
      uploadPayrollsGlobalBtn.addEventListener('click', () => {
        globalPayrollFileInput.click();
      });

      globalPayrollFileInput.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;
        
        await this.processPayrollFiles(files, null);
        // Limpiar el input
        globalPayrollFileInput.value = '';
        // Refrescar tabla
        this.renderEmployeesTable();
      });
    }

    // Event listeners para acciones de la tabla
    const tbody = document.getElementById('employeesTbody');
    if (tbody) {
      tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const action = btn.getAttribute('data-action');
        const id = btn.getAttribute('data-id');
        if (!action || !id) return;

        const employee = this.employees.find(emp => emp.id === id);
        if (!employee) return;

        if (action === 'view') {
          this.openEmployeeModal(id, true);
        } else if (action === 'edit') {
          this.openEmployeeModal(id);
        } else if (action === 'delete') {
          if (confirm(`¿Estás seguro de que quieres eliminar a ${employee.fullName}?`)) {
            this.deleteEmployee(id);
          }
        }
      });
    }
  }

  openEmployeeModal(employeeId = null, viewOnly = false) {
    const employee = employeeId ? this.employees.find(emp => emp.id === employeeId) : null;
    const isEdit = Boolean(employee);
    const canEdit =
      !viewOnly &&
      (typeof authManager !== "undefined" &&
        authManager &&
        (authManager.hasPermission("manageEmployees") || authManager.hasPermission("settings")));

    // Limitar tiendas disponibles a las asignadas al usuario (si aplica)
    const storesForAssignment = (() => {
      try {
        if (typeof authManager === "undefined" || !authManager) return STORES;
        const assigned = typeof authManager.getAssignedStores === "function" ? authManager.getAssignedStores() : null;
        if (!assigned) return STORES; // null => todas
        if (Array.isArray(assigned)) return STORES.filter((s) => assigned.includes(s.id));
        // compat: string
        return STORES.filter((s) => s.id === assigned);
      } catch (e) {
        console.warn("No se pudo filtrar tiendas por asignación:", e);
        return STORES;
      }
    })();

    // Crear modal
    const modal = document.createElement('div');
    modal.id = 'employeeModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-slate-900/40 p-4 z-50';
    modal.innerHTML = `
      <div class="w-full max-w-4xl rounded-2xl bg-white p-5 shadow-soft max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-base font-semibold">${isEdit ? (viewOnly ? 'Ver empleado' : 'Editar empleado') : 'Añadir empleado'}</div>
            <div class="text-sm text-slate-500">${isEdit ? 'Gestiona la información del empleado' : 'Completa todos los datos del nuevo empleado'}</div>
          </div>
          <button
            id="closeEmployeeModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form id="employeeForm" class="space-y-6">
          <input type="hidden" id="employeeId" value="${employee?.id || ''}" />

          <!-- Información Personal -->
          <div class="rounded-xl border-2 border-blue-100 bg-blue-50/30 p-4 ring-1 ring-blue-100">
            <div class="mb-4 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-blue-700">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
              </svg>
              <span class="text-sm font-semibold text-blue-900">Información Personal</span>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Nombre completo *</span>
                <input
                  id="employeeFullName"
                  type="text"
                  required
                  value="${this.escapeHtml(employee?.fullName || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">DNI *</span>
                <input
                  id="employeeDni"
                  type="text"
                  required
                  value="${this.escapeHtml(employee?.dni || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Número de teléfono</span>
                <input
                  id="employeePhone"
                  type="tel"
                  value="${this.escapeHtml(employee?.phone || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Correo electrónico</span>
                <input
                  id="employeeEmail"
                  type="email"
                  value="${this.escapeHtml(employee?.email || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block md:col-span-2">
                <span class="text-xs font-semibold text-slate-700">Calle</span>
                <input
                  id="employeeStreet"
                  type="text"
                  value="${this.escapeHtml(employee?.street || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Código postal</span>
                <input
                  id="employeePostalCode"
                  type="text"
                  value="${this.escapeHtml(employee?.postalCode || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Ciudad</span>
                <input
                  id="employeeCity"
                  type="text"
                  value="${this.escapeHtml(employee?.city || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
            </div>
          </div>

          <!-- Asociación de Usuario -->
          <div class="rounded-xl border-2 border-brand-100 bg-brand-50/30 p-4 ring-1 ring-brand-100">
            <div class="mb-4 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-brand-700">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span class="text-sm font-semibold text-brand-900">Cuenta de Usuario</span>
            </div>
            <div class="space-y-3">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Usuario asociado</span>
                <div class="mt-1 flex items-center gap-2">
                  <select
                    id="employeeUserId"
                    ${canEdit ? '' : 'disabled'}
                    class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                  >
                    <option value="">Sin usuario asignado</option>
                    <!-- Usuarios se cargan dinámicamente -->
                  </select>
                  ${canEdit ? `
                    <button
                      type="button"
                      id="createUserFromEmployeeBtn"
                      class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                      Crear usuario
                    </button>
                  ` : ''}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Asocia este empleado con una cuenta de usuario del sistema. Si no existe, puedes crear una nueva.
                </div>
              </label>
            </div>
          </div>

          <!-- Información Laboral -->
          <div class="rounded-xl border-2 border-emerald-100 bg-emerald-50/30 p-4 ring-1 ring-emerald-100">
            <div class="mb-4 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-emerald-700">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
              </svg>
              <span class="text-sm font-semibold text-emerald-900">Información Laboral</span>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Puesto *</span>
                <input
                  id="employeePosition"
                  type="text"
                  required
                  value="${this.escapeHtml(employee?.position || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Horas contratadas *</span>
                <input
                  id="employeeHours"
                  type="number"
                  required
                  min="0"
                  step="0.5"
                  value="${employee?.hours || ''}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha de inicio *</span>
                <input
                  id="employeeStartDate"
                  type="date"
                  required
                  value="${employee?.startDate || ''}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Fecha de finalización</span>
                <input
                  id="employeeEndDate"
                  type="date"
                  value="${employee?.endDate || ''}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block md:col-span-2">
                <span class="text-xs font-semibold text-slate-700">Tiendas a las que pertenece *</span>
                <div id="employeeStoresContainer" class="mt-2 space-y-2 rounded-xl border border-slate-200 bg-white p-3 ${canEdit ? '' : 'bg-slate-50'}">
                  ${storesForAssignment.map(store => {
                    const isChecked = employee?.storeIds 
                      ? employee.storeIds.includes(store.id)
                      : (employee?.storeId === store.id); // Compatibilidad con datos antiguos
                    return `
                      <label class="flex items-center gap-2 cursor-pointer ${canEdit ? '' : 'cursor-not-allowed opacity-50'}">
                        <input
                          type="checkbox"
                          value="${store.id}"
                          ${isChecked ? 'checked' : ''}
                          ${canEdit ? '' : 'disabled'}
                          class="employeeStoreCheckbox h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-2 focus:ring-brand-500"
                        />
                        <span class="text-sm text-slate-700">${this.escapeHtml(store.name)}</span>
                      </label>
                    `;
                  }).join('')}
                </div>
                <div id="employeeStoresError" class="mt-1 hidden text-xs text-rose-600">Debe seleccionar al menos una tienda</div>
              </label>
            </div>
          </div>

          <!-- Información Financiera -->
          <div class="rounded-xl border-2 border-amber-100 bg-amber-50/30 p-4 ring-1 ring-amber-100">
            <div class="mb-4 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-amber-700">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="text-sm font-semibold text-amber-900">Información Financiera</span>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Nº Seguridad Social</span>
                <input
                  id="employeeSocialSecurity"
                  type="text"
                  value="${this.escapeHtml(employee?.socialSecurity || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Número IBAN</span>
                <input
                  id="employeeIban"
                  type="text"
                  value="${this.escapeHtml(employee?.iban || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Salario bruto mensual (€)</span>
                <input
                  id="employeeGrossSalary"
                  type="number"
                  min="0"
                  step="0.01"
                  value="${employee?.grossSalary || ''}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Salario neto aproximado mensual (€)</span>
                <input
                  id="employeeNetSalary"
                  type="number"
                  min="0"
                  step="0.01"
                  value="${employee?.netSalary || ''}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
            </div>
          </div>

          <!-- Información de Uniforme -->
          <div class="rounded-xl border-2 border-purple-100 bg-purple-50/30 p-4 ring-1 ring-purple-100">
            <div class="mb-4 flex items-center gap-2">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-purple-700">
                <path d="M20 7h-3M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="text-sm font-semibold text-purple-900">Información de Uniforme</span>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Talla de camiseta</span>
                <input
                  id="employeeShirtSize"
                  type="text"
                  value="${this.escapeHtml(employee?.shirtSize || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Talla de blazer</span>
                <input
                  id="employeeBlazerSize"
                  type="text"
                  value="${this.escapeHtml(employee?.blazerSize || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
              <label class="block">
                <span class="text-xs font-semibold text-slate-700">Talla de pantalones</span>
                <input
                  id="employeePantsSize"
                  type="text"
                  value="${this.escapeHtml(employee?.pantsSize || '')}"
                  ${canEdit ? '' : 'readonly'}
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4 ${canEdit ? '' : 'bg-slate-50'}"
                />
              </label>
            </div>
          </div>

          <!-- Nóminas -->
          <div class="rounded-xl border-2 border-indigo-100 bg-indigo-50/30 p-4 ring-1 ring-indigo-100">
            <div class="mb-4 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-indigo-700">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="text-sm font-semibold text-indigo-900">Nóminas</span>
              </div>
              ${canEdit ? `
                <button
                  type="button"
                  id="uploadPayrollsBtn"
                  class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Subir nóminas (PDF)
                </button>
              ` : ''}
            </div>
            <div id="payrollsList" class="space-y-2">
              <!-- Nóminas se renderizan aquí -->
            </div>
            <input
              type="file"
              id="payrollFileInput"
              accept=".pdf"
              multiple
              class="hidden"
            />
          </div>

          ${canEdit ? `
            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-200">
              <button
                type="button"
                id="cancelEmployeeBtn"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
              >
                ${isEdit ? 'Guardar cambios' : 'Crear empleado'}
              </button>
            </div>
          ` : ''}
        </form>
      </div>
    `;

    document.body.appendChild(modal);

    // Cargar usuarios en el selector
    this.populateUserSelector(employee?.userId || null);

    // Renderizar nóminas existentes
    this.renderPayrolls(employeeId);

    // Event listeners para checkboxes de tiendas (ocultar error al seleccionar)
    const storeCheckboxes = document.querySelectorAll('.employeeStoreCheckbox');
    storeCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        const checkedCount = document.querySelectorAll('.employeeStoreCheckbox:checked').length;
        const errorDiv = document.getElementById('employeeStoresError');
        if (errorDiv) {
          if (checkedCount > 0) {
            errorDiv.classList.add('hidden');
          }
        }
      });
    });

    // Event listeners
    document.getElementById('closeEmployeeModalBtn').addEventListener('click', () => {
      modal.remove();
    });

    // Botón subir nóminas
    const uploadPayrollsBtn = document.getElementById('uploadPayrollsBtn');
    const payrollFileInput = document.getElementById('payrollFileInput');
    if (uploadPayrollsBtn && payrollFileInput) {
      uploadPayrollsBtn.addEventListener('click', () => {
        payrollFileInput.click();
      });

      payrollFileInput.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;
        
        await this.processPayrollFiles(files, employeeId);
        // Limpiar el input
        payrollFileInput.value = '';
      });
    }

    // Botón crear usuario desde empleado
    const createUserBtn = document.getElementById('createUserFromEmployeeBtn');
    if (createUserBtn && canEdit) {
      createUserBtn.addEventListener('click', () => {
        this.openCreateUserModal(employee);
      });
    }

    const cancelBtn = document.getElementById('cancelEmployeeBtn');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        modal.remove();
      });
    }

    const form = document.getElementById('employeeForm');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        // Solo permitir guardar si tiene permisos
        if (!canEdit) {
          alert('No tienes permisos para crear o editar empleados.');
          return;
        }
        this.handleFormSubmit(employeeId);
        modal.remove();
      });
    }
  }

  populateUserSelector(selectedUserId = null) {
    const select = document.getElementById('employeeUserId');
    if (!select) return;

    // Obtener todos los usuarios
    const users = typeof authManager !== 'undefined' && authManager 
      ? authManager.getAllUsers() 
      : [];

    // Limpiar opciones existentes (excepto la primera)
    while (select.options.length > 1) {
      select.remove(1);
    }

    // Añadir usuarios
    users.forEach(user => {
      const option = document.createElement('option');
      option.value = user.id;
      option.textContent = `${user.name || user.username} (${ROLES[user.role] ? ROLES[user.role].name : user.role})`;
      if (selectedUserId && user.id === selectedUserId) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  openCreateUserModal(employee = null) {
    // Limitar tiendas disponibles a las asignadas al usuario (si aplica)
    const storesForAssignment = (() => {
      try {
        if (typeof authManager === "undefined" || !authManager) return STORES;
        const assigned = typeof authManager.getAssignedStores === "function" ? authManager.getAssignedStores() : null;
        if (!assigned) return STORES; // null => todas
        if (Array.isArray(assigned)) return STORES.filter((s) => assigned.includes(s.id));
        // compat: string
        return STORES.filter((s) => s.id === assigned);
      } catch (e) {
        console.warn("No se pudo filtrar tiendas por asignación:", e);
        return STORES;
      }
    })();

    // Crear modal para crear usuario
    const modal = document.createElement('div');
    modal.id = 'createUserFromEmployeeModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-slate-900/40 p-4 z-50';
    modal.innerHTML = `
      <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-soft">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-base font-semibold">Crear usuario para empleado</div>
            <div class="text-sm text-slate-500">Crea una cuenta de usuario asociada a este empleado</div>
          </div>
          <button
            id="closeCreateUserModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form id="createUserFromEmployeeForm" class="space-y-3">
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Nombre de usuario *</span>
            <input
              id="newUserUsername"
              type="text"
              required
              placeholder="Ej: juan.perez"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            />
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Contraseña *</span>
            <input
              id="newUserPassword"
              type="password"
              required
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            />
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Rol *</span>
            <select
              id="newUserRole"
              required
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="empleado">Empleado</option>
              <option value="manager">Manager</option>
              <option value="visor">Visor</option>
              ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('admin.users.view') ? '<option value="admin">Administrador</option>' : ''}
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Tienda asignada</span>
            <select
              id="newUserStore"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="">Todas las tiendas</option>
              ${storesForAssignment.map(store => {
                const isSelected = employee?.storeIds 
                  ? employee.storeIds.includes(store.id)
                  : (employee?.storeId === store.id); // Compatibilidad con datos antiguos
                return `<option value="${store.id}" ${isSelected ? 'selected' : ''}>${this.escapeHtml(store.name)}</option>`;
              }).join('')}
            </select>
            <div class="mt-1 text-xs text-slate-500">Si se asigna una tienda, el usuario solo podrá ver/operar en esa tienda</div>
          </label>

          <div class="flex items-center justify-end gap-2 mt-4">
            <button
              type="button"
              id="cancelCreateUserBtn"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            >
              Crear usuario
            </button>
          </div>
        </form>
      </div>
    `;

    document.body.appendChild(modal);

    // Pre-llenar nombre de usuario si hay empleado
    if (employee && employee.fullName) {
      const usernameInput = document.getElementById('newUserUsername');
      if (usernameInput) {
        // Generar username sugerido desde el nombre
        const suggestedUsername = employee.fullName
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9\s]/g, '')
          .replace(/\s+/g, '.');
        usernameInput.value = suggestedUsername;
      }
    }

    // Event listeners
    document.getElementById('closeCreateUserModalBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('cancelCreateUserBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('createUserFromEmployeeForm').addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleCreateUserFromEmployee(employee);
      modal.remove();
    });
  }

  handleCreateUserFromEmployee(employee) {
    const username = document.getElementById('newUserUsername').value.trim();
    const password = document.getElementById('newUserPassword').value;
    const role = document.getElementById('newUserRole').value;
    const storeId = document.getElementById('newUserStore').value || null;

    if (!username || !password) {
      alert('El nombre de usuario y la contraseña son obligatorios.');
      return;
    }

    if (typeof authManager === 'undefined' || !authManager) {
      alert('Error: Sistema de autenticación no disponible.');
      return;
    }

    // Crear usuario
    const result = authManager.createUser({
      username,
      password,
      role,
      assignedStores: storeId ? [storeId] : null,
      name: employee?.fullName || username
    });

    if (result.success) {
      // Actualizar el selector de usuarios
      this.populateUserSelector(result.user.id);
      
      // Seleccionar el usuario recién creado
      const select = document.getElementById('employeeUserId');
      if (select) {
        select.value = result.user.id;
      }

      alert('Usuario creado correctamente. Ahora puedes guardar el empleado para asociarlo.');
    } else {
      alert(result.message || 'Error al crear el usuario.');
    }
  }

  handleFormSubmit(employeeId) {
    const formData = {
      fullName: document.getElementById('employeeFullName').value.trim(),
      dni: document.getElementById('employeeDni').value.trim(),
      phone: document.getElementById('employeePhone').value.trim(),
      email: document.getElementById('employeeEmail').value.trim(),
      street: document.getElementById('employeeStreet').value.trim(),
      postalCode: document.getElementById('employeePostalCode').value.trim(),
      city: document.getElementById('employeeCity').value.trim(),
      position: document.getElementById('employeePosition').value.trim(),
      hours: parseFloat(document.getElementById('employeeHours').value) || 0,
      startDate: document.getElementById('employeeStartDate').value,
      endDate: document.getElementById('employeeEndDate').value || null,
      storeIds: Array.from(document.querySelectorAll('.employeeStoreCheckbox:checked')).map(cb => cb.value),
      socialSecurity: document.getElementById('employeeSocialSecurity').value.trim(),
      iban: document.getElementById('employeeIban').value.trim(),
      grossSalary: parseFloat(document.getElementById('employeeGrossSalary').value) || 0,
      netSalary: parseFloat(document.getElementById('employeeNetSalary').value) || 0,
      shirtSize: document.getElementById('employeeShirtSize').value.trim(),
      blazerSize: document.getElementById('employeeBlazerSize').value.trim(),
      pantsSize: document.getElementById('employeePantsSize').value.trim(),
      userId: document.getElementById('employeeUserId').value || null,
    };

    // Migración: si hay address antigua, convertirla a street
    const existingEmployee = employeeId ? this.employees.find(emp => emp.id === employeeId) : null;
    if (existingEmployee && existingEmployee.address && !formData.street) {
      formData.street = existingEmployee.address;
    }

    // Validaciones básicas
    if (!formData.fullName) {
      alert('El nombre completo es obligatorio.');
      return;
    }
    if (!formData.dni) {
      alert('El DNI es obligatorio.');
      return;
    }
    if (!formData.position) {
      alert('El puesto es obligatorio.');
      return;
    }
    if (!formData.storeIds || formData.storeIds.length === 0) {
      const errorDiv = document.getElementById('employeeStoresError');
      if (errorDiv) {
        errorDiv.classList.remove('hidden');
      }
      alert('Debe seleccionar al menos una tienda.');
      return;
    }
    // Ocultar error si hay tiendas seleccionadas
    const errorDiv = document.getElementById('employeeStoresError');
    if (errorDiv) {
      errorDiv.classList.add('hidden');
    }
    if (!formData.startDate) {
      alert('La fecha de inicio es obligatoria.');
      return;
    }
    if (formData.hours <= 0) {
      alert('Las horas contratadas deben ser mayores a 0.');
      return;
    }

    if (employeeId) {
      // Editar
      const index = this.employees.findIndex(emp => emp.id === employeeId);
      if (index === -1) {
        alert('Empleado no encontrado.');
        return;
      }
      // Eliminar address si existe (migración)
      // Eliminar storeId si existe (migración a storeIds)
      const updatedData = { ...formData };
      if (updatedData.address) {
        delete updatedData.address;
      }
      if (updatedData.storeId) {
        delete updatedData.storeId;
      }
      this.employees[index] = {
        ...this.employees[index],
        ...updatedData,
        updatedAt: new Date().toISOString()
      };
    } else {
      // Crear
      const newEmployee = {
        id: 'emp-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
        ...formData,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      };
      // Asegurar que no tenga address ni storeId antiguo
      if (newEmployee.address) {
        delete newEmployee.address;
      }
      if (newEmployee.storeId) {
        delete newEmployee.storeId;
      }
      this.employees.push(newEmployee);
    }

    this.saveEmployees();
    this.renderEmployeesTable();
  }

  deleteEmployee(employeeId) {
    const index = this.employees.findIndex(emp => emp.id === employeeId);
    if (index === -1) {
      alert('Empleado no encontrado.');
      return;
    }

    this.employees.splice(index, 1);
    this.saveEmployees();
    this.renderEmployeesTable();
  }

  renderPayrolls(employeeId) {
    const payrollsList = document.getElementById('payrollsList');
    if (!payrollsList) return;

    const employee = employeeId ? this.employees.find(emp => emp.id === employeeId) : null;
    const payrolls = employee?.payrolls || [];

    if (payrolls.length === 0) {
      payrollsList.innerHTML = `
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-500">
          No hay nóminas asociadas. Haz clic en "Subir nóminas (PDF)" para agregar.
        </div>
      `;
      return;
    }

    payrollsList.innerHTML = '';
    payrolls.forEach((payroll, index) => {
      const div = document.createElement('div');
      div.className = 'flex items-center justify-between rounded-lg border border-slate-200 bg-white p-3 ring-1 ring-slate-100';
      
      const date = payroll.date || payroll.uploadedAt || 'Sin fecha';
      const fileName = payroll.fileName || `Nómina ${index + 1}`;
      
      div.innerHTML = `
        <div class="flex items-center gap-3">
          <div class="grid h-10 w-10 place-items-center rounded-lg bg-indigo-100 text-indigo-700">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div>
            <div class="text-sm font-semibold text-slate-900">${this.escapeHtml(fileName)}</div>
            <div class="text-xs text-slate-500">${date}</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            data-action="view-payroll"
            data-employee-id="${employeeId}"
            data-payroll-index="${index}"
            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 ring-1 ring-transparent hover:ring-brand-100"
          >
            Ver
          </button>
          ${typeof authManager !== 'undefined' && authManager && authManager.hasPermission('hr.employees.configure') ? `
            <button
              type="button"
              data-action="delete-payroll"
              data-employee-id="${employeeId}"
              data-payroll-index="${index}"
              class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 ring-1 ring-transparent hover:ring-rose-100"
            >
              Eliminar
            </button>
          ` : ''}
        </div>
      `;
      payrollsList.appendChild(div);
    });

    // Event listeners para botones
    payrollsList.querySelectorAll('[data-action="view-payroll"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const empId = btn.getAttribute('data-employee-id');
        const index = parseInt(btn.getAttribute('data-payroll-index'));
        this.viewPayroll(empId, index);
      });
    });

    payrollsList.querySelectorAll('[data-action="delete-payroll"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const empId = btn.getAttribute('data-employee-id');
        const index = parseInt(btn.getAttribute('data-payroll-index'));
        if (confirm('¿Estás seguro de que quieres eliminar esta nómina?')) {
          this.deletePayroll(empId, index);
        }
      });
    });
  }

  async processPayrollFiles(files, currentEmployeeId) {
    if (typeof pdfjsLib === 'undefined') {
      alert('Error: La librería PDF.js no está cargada. Recarga la página.');
      return;
    }

    // Configurar worker de PDF.js
    if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
      pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }

    const canEdit = typeof authManager !== 'undefined' && authManager && authManager.hasPermission('hr.employees.configure');
    if (!canEdit) {
      alert('No tienes permisos para subir nóminas.');
      return;
    }

    const results = [];
    const errors = [];

    for (const file of files) {
      try {
        // Leer archivo como ArrayBuffer
        const arrayBuffer = await file.arrayBuffer();
        
        // Cargar PDF
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const numPages = pdf.numPages;
        
        // Extraer texto de todas las páginas
        let fullText = '';
        for (let i = 1; i <= numPages; i++) {
          const page = await pdf.getPage(i);
          const textContent = await page.getTextContent();
          const pageText = textContent.items.map(item => item.str).join(' ');
          fullText += pageText + '\n';
        }

        // Convertir a base64 para almacenamiento
        const base64 = await this.arrayBufferToBase64(arrayBuffer);

        // Buscar empleado correspondiente
        const matchedEmployee = this.findEmployeeByPayrollData(fullText, currentEmployeeId);
        
        if (matchedEmployee) {
          // Extraer fecha de la nómina
          const extractedDate = this.extractPayrollDate(fullText) || new Date().toISOString().split('T')[0];
          
          // Generar nombre en formato "NOMBRE MES AÑO"
          const payrollFileName = this.generatePayrollFileName(
            matchedEmployee.employee.fullName,
            extractedDate
          );
          
          // Agregar nómina al empleado
          const payroll = {
            id: 'payroll-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
            fileName: payrollFileName,
            date: extractedDate,
            base64: base64,
            uploadedAt: new Date().toISOString(),
            matchedBy: matchedEmployee.matchType
          };

          if (!matchedEmployee.employee.payrolls) {
            matchedEmployee.employee.payrolls = [];
          }
          matchedEmployee.employee.payrolls.push(payroll);
          
          results.push({
            fileName: file.name,
            employeeName: matchedEmployee.employee.fullName,
            matchType: matchedEmployee.matchType
          });
        } else {
          // Si hay un empleado actual, agregar a ese
          if (currentEmployeeId) {
            const employee = this.employees.find(emp => emp.id === currentEmployeeId);
            if (employee) {
              // Extraer fecha de la nómina
              const extractedDate = this.extractPayrollDate(fullText) || new Date().toISOString().split('T')[0];
              
              // Generar nombre en formato "NOMBRE MES AÑO"
              const payrollFileName = this.generatePayrollFileName(
                employee.fullName,
                extractedDate
              );
              
              const payroll = {
                id: 'payroll-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                fileName: payrollFileName,
                date: extractedDate,
                base64: base64,
                uploadedAt: new Date().toISOString(),
                matchedBy: 'manual'
              };

              if (!employee.payrolls) {
                employee.payrolls = [];
              }
              employee.payrolls.push(payroll);
              
              results.push({
                fileName: file.name,
                employeeName: employee.fullName,
                matchType: 'manual'
              });
            } else {
              errors.push({ fileName: file.name, error: 'Empleado no encontrado' });
            }
          } else {
            errors.push({ fileName: file.name, error: 'No se pudo identificar el empleado. Abre la ficha del empleado primero.' });
          }
        }
      } catch (error) {
        console.error('Error procesando PDF:', error);
        errors.push({ fileName: file.name, error: error.message || 'Error al procesar el PDF' });
      }
    }

    // Guardar cambios
    this.saveEmployees();

    // Mostrar resultados
    let message = '';
    if (results.length > 0) {
      message += `Nóminas procesadas correctamente:\n`;
      results.forEach(r => {
        message += `• ${r.fileName} → ${r.employeeName} (${r.matchType === 'dni' ? 'por DNI' : r.matchType === 'socialSecurity' ? 'por Seguridad Social' : r.matchType === 'name' ? 'por nombre' : 'manual'})\n`;
      });
    }
    if (errors.length > 0) {
      message += `\nErrores:\n`;
      errors.forEach(e => {
        message += `• ${e.fileName}: ${e.error}\n`;
      });
    }

    if (message) {
      alert(message);
    }

    // Actualizar vista de nóminas si estamos viendo un empleado
    if (currentEmployeeId) {
      this.renderPayrolls(currentEmployeeId);
    }
  }

  findEmployeeByPayrollData(pdfText, currentEmployeeId) {
    const normalizedText = pdfText.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    
    for (const employee of this.employees) {
      // Buscar por DNI
      if (employee.dni) {
        const dniNormalized = employee.dni.replace(/[-\s]/g, '').toUpperCase();
        const dniPattern = new RegExp(dniNormalized.replace(/(.)/g, '$1[-\s]?'), 'i');
        if (dniPattern.test(pdfText)) {
          return { employee, matchType: 'dni' };
        }
      }

      // Buscar por número de seguridad social
      if (employee.socialSecurity) {
        const ssNormalized = employee.socialSecurity.replace(/[-\s]/g, '');
        if (normalizedText.includes(ssNormalized.toLowerCase()) || pdfText.includes(ssNormalized)) {
          return { employee, matchType: 'socialSecurity' };
        }
      }

      // Buscar por nombre completo
      if (employee.fullName) {
        const nameParts = employee.fullName.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').split(/\s+/);
        // Buscar que aparezcan al menos 2 partes del nombre
        let matches = 0;
        for (const part of nameParts) {
          if (part.length > 2 && normalizedText.includes(part)) {
            matches++;
          }
        }
        if (matches >= 2) {
          return { employee, matchType: 'name' };
        }
      }
    }

    return null;
  }

  extractPayrollDate(pdfText) {
    // Buscar patrones de fecha comunes en nóminas españolas
    const datePatterns = [
      /(?:nómina|nomina|periodo|período)[\s:]*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/i,
      /(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,
      /(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/
    ];

    for (const pattern of datePatterns) {
      const match = pdfText.match(pattern);
      if (match) {
        let day, month, year;
        if (match[3] && match[3].length === 4) {
          // Formato DD/MM/YYYY o DD-MM-YYYY
          day = match[1].padStart(2, '0');
          month = match[2].padStart(2, '0');
          year = match[3];
        } else {
          // Formato YYYY/MM/DD o YYYY-MM-DD
          year = match[1];
          month = match[2].padStart(2, '0');
          day = match[3].padStart(2, '0');
        }
        return `${year}-${month}-${day}`;
      }
    }

    return null;
  }

  generatePayrollFileName(employeeName, dateString) {
    // Formato: "NOMBRE MES AÑO" (ejemplo: "MARIAN NOVIEMBRE 2025")
    const months = [
      'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
      'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
    ];
    
    let month = '';
    let year = '';
    
    if (dateString) {
      // dateString puede ser "YYYY-MM-DD" o cualquier formato
      const dateMatch = dateString.match(/(\d{4})[\/\-](\d{1,2})/);
      if (dateMatch) {
        year = dateMatch[1];
        const monthNum = parseInt(dateMatch[2], 10);
        if (monthNum >= 1 && monthNum <= 12) {
          month = months[monthNum - 1];
        }
      }
    }
    
    // Si no se pudo extraer la fecha, usar la fecha actual
    if (!month || !year) {
      const now = new Date();
      month = months[now.getMonth()];
      year = now.getFullYear().toString();
    }
    
    // Normalizar nombre del empleado (mayúsculas, sin acentos)
    const normalizedName = (employeeName || 'EMPLEADO')
      .toUpperCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
    
    return `${normalizedName} ${month} ${year}`;
  }

  arrayBufferToBase64(buffer) {
    return new Promise((resolve) => {
      const blob = new Blob([buffer], { type: 'application/pdf' });
      const reader = new FileReader();
      reader.onloadend = () => {
        const base64 = reader.result.split(',')[1];
        resolve(base64);
      };
      reader.readAsDataURL(blob);
    });
  }

  viewPayroll(employeeId, payrollIndex) {
    const employee = this.employees.find(emp => emp.id === employeeId);
    if (!employee || !employee.payrolls || !employee.payrolls[payrollIndex]) {
      alert('Nómina no encontrada.');
      return;
    }

    const payroll = employee.payrolls[payrollIndex];
    const base64 = payroll.base64;
    
    // Crear URL del blob
    const byteCharacters = atob(base64);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    
    // Crear modal para visualizar el PDF
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4';
    modal.innerHTML = `
      <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 p-4">
          <h2 class="text-lg font-semibold text-slate-900">${this.escapeHtml(payroll.fileName || 'Nómina')}</h2>
          <button
            id="closePayrollModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
        <div class="p-4">
          <iframe
            src="${url}"
            class="w-full h-[80vh] rounded-lg border border-slate-200"
            type="application/pdf"
          ></iframe>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    
    // Cerrar modal
    const closeBtn = modal.querySelector('#closePayrollModalBtn');
    closeBtn.addEventListener('click', () => {
      URL.revokeObjectURL(url);
      modal.remove();
    });
    
    // Cerrar al hacer clic fuera del modal
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        URL.revokeObjectURL(url);
        modal.remove();
      }
    });
  }

  deletePayroll(employeeId, payrollIndex) {
    const employee = this.employees.find(emp => emp.id === employeeId);
    if (!employee || !employee.payrolls || !employee.payrolls[payrollIndex]) {
      alert('Nómina no encontrada.');
      return;
    }

    employee.payrolls.splice(payrollIndex, 1);
    this.saveEmployees();
    this.renderPayrolls(employeeId);
  }

  showCSVMenu(button) {
    // Eliminar menú anterior si existe
    const existingMenu = document.getElementById('csvMenuDropdown');
    if (existingMenu) {
      existingMenu.remove();
      return;
    }

    // Crear menú desplegable
    const menu = document.createElement('div');
    menu.id = 'csvMenuDropdown';
    menu.className = 'absolute z-50 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-slate-100';
    
    const buttonRect = button.getBoundingClientRect();
    menu.style.position = 'fixed';
    menu.style.top = (buttonRect.bottom + 8) + 'px';
    menu.style.right = (window.innerWidth - buttonRect.right) + 'px';

    menu.innerHTML = `
      <div class="p-2">
        <button
          id="exportCSVBtn"
          class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Exportar plantilla CSV
        </button>
        <button
          id="importCSVBtn"
          class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 mt-1"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Importar CSV
        </button>
      </div>
    `;

    document.body.appendChild(menu);

    // Event listeners
    document.getElementById('exportCSVBtn').addEventListener('click', () => {
      this.exportEmployeesToCSV();
      menu.remove();
    });

    document.getElementById('importCSVBtn').addEventListener('click', () => {
      this.importEmployeesFromCSV();
      menu.remove();
    });

    // Cerrar al hacer clic fuera
    setTimeout(() => {
      document.addEventListener('click', function closeMenu(e) {
        if (!menu.contains(e.target) && e.target !== button && !button.contains(e.target)) {
          menu.remove();
          document.removeEventListener('click', closeMenu);
        }
      });
    }, 0);
  }

  exportEmployeesToCSV() {
    // Crear plantilla CSV con todos los campos posibles
    const headers = [
      'Nombre completo',
      'DNI',
      'Teléfono',
      'Correo electrónico',
      'Calle',
      'Código postal',
      'Ciudad',
      'Puesto',
      'Horas semanales',
      'Fecha inicio',
      'Fecha fin',
      'Tiendas (separadas por coma)',
      'Seguridad Social',
      'IBAN',
      'Salario bruto',
      'Salario neto',
      'Talla camiseta',
      'Talla chaqueta',
      'Talla pantalón'
    ];

    // Crear CSV con encabezados
    let csv = headers.join(',') + '\n';

    // Añadir fila de ejemplo vacía
    csv += headers.map(() => '').join(',') + '\n';

    // Crear blob y descargar
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `plantilla_empleados_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  importEmployeesFromCSV() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv';
    input.style.display = 'none';

    input.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (event) => {
        try {
          const csv = event.target.result;
          const lines = csv.split('\n').filter(line => line.trim());
          
          if (lines.length < 2) {
            alert('El archivo CSV debe tener al menos una fila de encabezados y una fila de datos.');
            return;
          }

          // Parsear CSV (manejar comillas y comas dentro de campos)
          const parseCSVLine = (line) => {
            const result = [];
            let current = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
              const char = line[i];
              if (char === '"') {
                inQuotes = !inQuotes;
              } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
              } else {
                current += char;
              }
            }
            result.push(current.trim());
            return result;
          };

          const headers = parseCSVLine(lines[0]);
          const dataRows = lines.slice(1).filter(line => line.trim());

          if (dataRows.length === 0) {
            alert('No hay datos en el archivo CSV.');
            return;
          }

          // Mapear índices de columnas
          const getIndex = (name) => {
            const index = headers.findIndex(h => 
              h.toLowerCase().trim() === name.toLowerCase().trim()
            );
            return index >= 0 ? index : -1;
          };

          const nameIdx = getIndex('Nombre completo');
          const dniIdx = getIndex('DNI');
          const phoneIdx = getIndex('Teléfono');
          const emailIdx = getIndex('Correo electrónico');
          const streetIdx = getIndex('Calle');
          const postalCodeIdx = getIndex('Código postal');
          const cityIdx = getIndex('Ciudad');
          const positionIdx = getIndex('Puesto');
          const hoursIdx = getIndex('Horas semanales');
          const startDateIdx = getIndex('Fecha inicio');
          const endDateIdx = getIndex('Fecha fin');
          const storeIdx = getIndex('Tienda');
          const socialSecurityIdx = getIndex('Seguridad Social');
          const ibanIdx = getIndex('IBAN');
          const grossSalaryIdx = getIndex('Salario bruto');
          const netSalaryIdx = getIndex('Salario neto');
          const shirtSizeIdx = getIndex('Talla camiseta');
          const blazerSizeIdx = getIndex('Talla chaqueta');
          const pantsSizeIdx = getIndex('Talla pantalón');

          if (nameIdx === -1) {
            alert('Error: El archivo CSV debe tener una columna "Nombre completo".');
            return;
          }

          let imported = 0;
          let updated = 0;
          let errors = [];

          dataRows.forEach((row, index) => {
            try {
              const values = parseCSVLine(row);
              const fullName = values[nameIdx]?.trim() || '';
              
              if (!fullName) {
                errors.push(`Fila ${index + 2}: Nombre completo vacío`);
                return;
              }

              // Buscar si ya existe un empleado con el mismo nombre o DNI
              const dni = values[dniIdx]?.trim() || '';
              let existingEmployee = null;
              
              if (dni) {
                existingEmployee = this.employees.find(emp => emp.dni && emp.dni.toLowerCase() === dni.toLowerCase());
              }
              
              if (!existingEmployee) {
                existingEmployee = this.employees.find(emp => 
                  emp.fullName.toLowerCase() === fullName.toLowerCase()
                );
              }

              // Mapear nombres de tiendas a IDs (puede haber múltiples separados por coma o punto y coma)
              let storeIds = [];
              const storeValue = values[storeIdx]?.trim() || '';
              if (storeValue && typeof STORES !== 'undefined') {
                // Separar por coma o punto y coma
                const storeNames = storeValue.split(/[,;]/).map(s => s.trim()).filter(Boolean);
                
                storeNames.forEach(storeNameOrId => {
                  // Buscar por nombre completo
                  const storeByName = STORES.find(s => 
                    s.name.toLowerCase() === storeNameOrId.toLowerCase()
                  );
                  if (storeByName) {
                    if (!storeIds.includes(storeByName.id)) {
                      storeIds.push(storeByName.id);
                    }
                  } else {
                    // Buscar por ID
                    const storeById = STORES.find(s => s.id === storeNameOrId);
                    if (storeById && !storeIds.includes(storeById.id)) {
                      storeIds.push(storeById.id);
                    } else {
                      // Intentar buscar por coincidencia parcial
                      const partialMatch = STORES.find(s => 
                        (s.name.toLowerCase().includes(storeNameOrId.toLowerCase()) ||
                        storeNameOrId.toLowerCase().includes(s.name.toLowerCase())) &&
                        !storeIds.includes(s.id)
                      );
                      if (partialMatch) {
                        storeIds.push(partialMatch.id);
                      }
                    }
                  }
                });
              }

              const employeeData = {
                fullName: fullName,
                dni: dni || '',
                phone: values[phoneIdx]?.trim() || '',
                email: values[emailIdx]?.trim() || '',
                street: values[streetIdx]?.trim() || '',
                postalCode: values[postalCodeIdx]?.trim() || '',
                city: values[cityIdx]?.trim() || '',
                position: values[positionIdx]?.trim() || '',
                hours: parseFloat(values[hoursIdx]?.replace(',', '.') || '0') || 0,
                startDate: values[startDateIdx]?.trim() || '',
                endDate: values[endDateIdx]?.trim() || null,
                storeIds: storeIds.length > 0 ? storeIds : [],
                socialSecurity: values[socialSecurityIdx]?.trim() || '',
                iban: values[ibanIdx]?.trim() || '',
                grossSalary: parseFloat(values[grossSalaryIdx]?.replace(',', '.') || '0') || 0,
                netSalary: parseFloat(values[netSalaryIdx]?.replace(',', '.') || '0') || 0,
                shirtSize: values[shirtSizeIdx]?.trim() || '',
                blazerSize: values[blazerSizeIdx]?.trim() || '',
                pantsSize: values[pantsSizeIdx]?.trim() || '',
                payrolls: existingEmployee?.payrolls || [],
                userId: existingEmployee?.userId || null
              };

              if (existingEmployee) {
                // Actualizar empleado existente
                Object.assign(existingEmployee, employeeData, {
                  updatedAt: new Date().toISOString()
                });
                updated++;
              } else {
                // Crear nuevo empleado
                const newEmployee = {
                  id: 'emp-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                  ...employeeData,
                  createdAt: new Date().toISOString(),
                  updatedAt: new Date().toISOString()
                };
                this.employees.push(newEmployee);
                imported++;
              }
            } catch (error) {
              errors.push(`Fila ${index + 2}: ${error.message}`);
            }
          });

          // Guardar cambios
          this.saveEmployees();

          // Mostrar resultados
          let message = `Importación completada:\n`;
          message += `• ${imported} empleado(s) importado(s)\n`;
          message += `• ${updated} empleado(s) actualizado(s)`;
          if (errors.length > 0) {
            message += `\n\nErrores:\n${errors.slice(0, 10).join('\n')}`;
            if (errors.length > 10) {
              message += `\n... y ${errors.length - 10} error(es) más`;
            }
          }
          alert(message);

          // Refrescar tabla
          this.renderEmployeesTable();
        } catch (error) {
          console.error('Error importando CSV:', error);
          alert('Error al importar el archivo CSV: ' + error.message);
        }
      };

      reader.readAsText(file, 'UTF-8');
    });

    input.click();
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  getAllEmployees() {
    return this.employees;
  }
}

// Instancia global del gestor de empleados
// Se inicializa en app.js después de la autenticación
let employeeManager;
