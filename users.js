// Gestión de usuarios y roles (solo para administradores)
class UserManager {
  constructor() {
    this.initializeUI();
  }

  initializeUI() {
    // Crear panel de administración en el sidebar si es admin
    this.createAdminPanel();
    this.renderUsersTable();
    this.initializeEventListeners();
  }

  createAdminPanel() {
    const sidebar = document.querySelector('aside nav');
    if (!sidebar) return;

    // Verificar si ya existe
    if (document.getElementById('adminPanelLink')) return;

    const adminLink = document.createElement('a');
    adminLink.id = 'adminPanelLink';
    adminLink.href = '#';
    adminLink.className = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50';
    adminLink.innerHTML = `
      <span class="text-slate-500" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </span>
      Administración
    `;

    adminLink.addEventListener('click', (e) => {
      e.preventDefault();
      this.showAdminPanel();
    });

    sidebar.appendChild(adminLink);
  }

  showAdminPanel() {
    // Ocultar contenido principal
    const main = document.querySelector('main');
    if (!main) return;

    // Crear o mostrar panel de administración
    let adminPanel = document.getElementById('adminPanel');
    if (!adminPanel) {
      adminPanel = this.createAdminPanelContent();
      main.innerHTML = '';
      main.appendChild(adminPanel);
    } else {
      main.innerHTML = '';
      main.appendChild(adminPanel);
    }

    // Mostrar la vista principal (menú de apartados)
    this.showMainView();
  }

  showMainView() {
    const container = document.getElementById('adminContentContainer');
    if (!container) return;

    container.innerHTML = `
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Apartado de Usuarios -->
        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
          <div class="mb-4">
            <div class="flex items-center gap-3 mb-2">
              <div class="grid h-12 w-12 place-items-center rounded-xl bg-brand-100 text-brand-700">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <div class="text-lg font-semibold">Usuarios</div>
                <div class="text-sm text-slate-500">Gestiona las cuentas de usuario</div>
              </div>
            </div>
            <p class="text-sm text-slate-600 mt-3">
              Crea, edita y elimina usuarios. Asigna roles y tiendas a cada usuario.
            </p>
          </div>
          <button
            id="editUsersBtn"
            class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Editar usuarios
          </button>
        </div>

        <!-- Apartado de Roles y Permisos -->
        <div class="rounded-2xl bg-white p-6 shadow-soft ring-1 ring-slate-100">
          <div class="mb-4">
            <div class="flex items-center gap-3 mb-2">
              <div class="grid h-12 w-12 place-items-center rounded-xl bg-emerald-100 text-emerald-700">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="2"/>
                  <path d="M19.4 15a7.97 7.97 0 0 0 .1-2 7.97 7.97 0 0 0-.1-2l2-1.5-2-3.5-2.4.6a8 8 0 0 0-3.4-2l-.4-2.5h-4l-.4 2.5a8 8 0 0 0-3.4 2L3.1 6l-2 3.5L3.1 11a7.97 7.97 0 0 0-.1 2c0 .68.03 1.34.1 2L1.1 16.5l2 3.5 2.4-.6a8 8 0 0 0 3.4 2l.4 2.5h4l.4-2.5a8 8 0 0 0 3.4-2l2.4.6 2-3.5-2-1.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div>
                <div class="text-lg font-semibold">Roles y Permisos</div>
                <div class="text-sm text-slate-500">Personaliza los permisos de cada rol</div>
              </div>
            </div>
            <p class="text-sm text-slate-600 mt-3">
              Configura qué puede hacer cada rol: crear, editar, eliminar registros y más.
            </p>
          </div>
          <button
            id="editRolesBtn"
            class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Editar roles
          </button>
        </div>
      </div>
    `;

    // Event listeners para los botones
    document.getElementById('editUsersBtn').addEventListener('click', () => {
      this.showUsersView();
    });

    document.getElementById('editRolesBtn').addEventListener('click', () => {
      this.showRolesView();
    });
  }

  showUsersView() {
    const container = document.getElementById('adminContentContainer');
    if (!container) return;

    container.innerHTML = `
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Gestión de Usuarios</h2>
            <p class="text-sm text-slate-500">Crea, edita y elimina usuarios del sistema</p>
          </div>
          <button
            id="backToMainBtn"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            ← Volver
          </button>
        </div>

        <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <div>
            <div class="text-sm font-semibold">Usuarios</div>
            <div class="text-xs text-slate-500">Gestiona las cuentas de usuario</div>
          </div>
          <button
            id="addUserBtn"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Añadir usuario
          </button>
        </div>

        <div id="usersTableContainer" class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <!-- Tabla de usuarios se renderiza aquí -->
        </div>

        <div class="flex items-center justify-end gap-2 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <button
            id="cancelUsersBtn"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Cancelar
          </button>
          <button
            id="saveUsersBtn"
            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
          >
            Guardar cambios
          </button>
        </div>
      </div>
    `;

    // Event listeners
    document.getElementById('backToMainBtn').addEventListener('click', () => {
      this.showMainView();
    });

    document.getElementById('addUserBtn').addEventListener('click', () => {
      this.openUserModal();
    });

    document.getElementById('cancelUsersBtn').addEventListener('click', () => {
      this.showMainView();
    });

    document.getElementById('saveUsersBtn').addEventListener('click', () => {
      // Los cambios de usuarios ya se guardan automáticamente al crear/editar/eliminar
      // Este botón solo confirma y vuelve
      alert('Los cambios de usuarios se han guardado correctamente.');
      this.showMainView();
    });

    // Renderizar tabla de usuarios
    this.renderUsersTable();
  }

  showRolesView() {
    const container = document.getElementById('adminContentContainer');
    if (!container) return;

    // Guardar estado actual de roles antes de editar
    this.rolesBackup = JSON.parse(JSON.stringify(ROLES));

    container.innerHTML = `
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Roles y Permisos</h2>
            <p class="text-sm text-slate-500">Personaliza los permisos de cada rol</p>
          </div>
          <button
            id="backToMainBtn"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            ← Volver
          </button>
        </div>

        <div id="rolesEditorContainer" class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <!-- Editor de roles se renderiza aquí -->
        </div>

        <div class="flex items-center justify-end gap-2 rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
          <button
            id="cancelRolesBtn"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Cancelar
          </button>
          <button
            id="saveRolesBtn"
            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
          >
            Guardar cambios
          </button>
        </div>
      </div>
    `;

    // Event listeners
    document.getElementById('backToMainBtn').addEventListener('click', () => {
      // Restaurar backup si no se guardó
      if (this.rolesBackup) {
        ROLES = JSON.parse(JSON.stringify(this.rolesBackup));
      }
      this.showMainView();
    });

    document.getElementById('cancelRolesBtn').addEventListener('click', () => {
      // Restaurar backup
      if (this.rolesBackup) {
        ROLES = JSON.parse(JSON.stringify(this.rolesBackup));
        // Recargar la vista para mostrar los valores originales
        this.showRolesView();
      }
    });

    document.getElementById('saveRolesBtn').addEventListener('click', () => {
      // Guardar cambios
      saveCustomRoles();
      this.rolesBackup = null;
      alert('Los cambios de roles y permisos se han guardado correctamente.');
      
      // Si el usuario actual tiene este rol, actualizar permisos
      if (authManager.getCurrentUser()) {
        if (typeof roleManager !== 'undefined') {
          roleManager.applyRolePermissions();
        }
      }
      
      this.showMainView();
    });

    // Renderizar editor de roles
    this.renderRolesEditor();
  }

  createAdminPanelContent() {
    const panel = document.createElement('div');
    panel.id = 'adminPanel';
    panel.className = 'space-y-6';

    panel.innerHTML = `
      <header class="rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-100">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Administración</h1>
            <p class="text-sm text-slate-500">Gestiona usuarios, roles y permisos del sistema.</p>
          </div>
        </div>
      </header>

      <div id="adminContentContainer">
        <!-- El contenido se renderiza dinámicamente -->
      </div>
    `;

    return panel;
  }

  initializeEventListeners() {
  }

  renderUsersTable() {
    const container = document.getElementById('usersTableContainer');
    if (!container) return;

    const users = authManager.getAllUsers();
    
    if (users.length === 0) {
      container.innerHTML = '<p class="text-sm text-slate-500">No hay usuarios registrados.</p>';
      return;
    }

    let html = `
      <table class="min-w-full text-left text-sm">
        <thead class="text-xs uppercase text-slate-500">
          <tr>
            <th class="px-3 py-2">Usuario</th>
            <th class="px-3 py-2">Nombre</th>
            <th class="px-3 py-2">Rol</th>
            <th class="px-3 py-2">Tienda</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
    `;

    users.forEach(user => {
      const roleName = ROLES[user.role] ? ROLES[user.role].name : user.role;
      const storeName = user.assignedStores 
        ? (Array.isArray(user.assignedStores) 
          ? user.assignedStores.map(s => STORES.find(store => store.id === s)?.name || s).join(', ')
          : STORES.find(s => s.id === user.assignedStores)?.name || user.assignedStores)
        : (user.assignedStore 
          ? STORES.find(s => s.id === user.assignedStore)?.name || user.assignedStore
          : 'Todas');
      
      html += `
        <tr class="hover:bg-slate-50">
          <td class="px-3 py-2 font-medium">${user.username}</td>
          <td class="px-3 py-2">${user.name || '-'}</td>
          <td class="px-3 py-2">
            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800">
              ${roleName}
            </span>
          </td>
          <td class="px-3 py-2 text-xs text-slate-600">${storeName}</td>
          <td class="px-3 py-2">
            <div class="flex items-center gap-2">
              <button
                data-action="edit-user"
                data-id="${user.id}"
                class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100"
                title="Editar"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
              <button
                data-action="delete-user"
                data-id="${user.id}"
                class="rounded-lg p-1.5 text-rose-600 hover:bg-rose-50"
                title="Eliminar"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    html += '</tbody></table>';

    container.innerHTML = html;

    // Event listeners para botones
    container.querySelectorAll('[data-action="edit-user"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const userId = btn.getAttribute('data-id');
        this.openUserModal(userId);
      });
    });

    container.querySelectorAll('[data-action="delete-user"]').forEach(btn => {
      btn.addEventListener('click', () => {
        const userId = btn.getAttribute('data-id');
        if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
          const result = authManager.deleteUser(userId);
          if (result.success) {
            this.renderUsersTable();
          } else {
            alert(result.message);
          }
        }
      });
    });
  }

  renderRolesEditor() {
    const container = document.getElementById('rolesEditorContainer');
    if (!container) return;

    let html = '<div class="space-y-4">';

    Object.keys(ROLES).forEach(roleKey => {
      const role = ROLES[roleKey];
      html += `
        <div class="rounded-xl border border-slate-200 p-4">
          <div class="mb-3 flex items-center justify-between">
            <div>
              <div class="text-sm font-semibold">${role.name}</div>
              <div class="text-xs text-slate-500">Nivel ${role.level}</div>
            </div>
          </div>
          <div class="space-y-2">
      `;

      Object.keys(role.permissions).forEach(permission => {
        const permissionLabels = {
          view: 'Visualizar',
          create: 'Crear registros',
          createTypes: 'Tipos de registro (crear)',
          edit: 'Editar registros',
          delete: 'Eliminar registros',
          export: 'Exportar datos',
          settings: 'Configuración',
          manageUsers: 'Gestionar usuarios',
          manageEmployees: 'Gestionar empleados',
          manageOrders: 'Gestionar pedidos',
          viewOwnEmployee: 'Ver propia ficha de empleado',
          viewOptions: 'Opciones de visualización'
        };

        // El objeto createTypes se renderiza dentro de "Crear registros"
        if (permission === "createTypes") {
          return;
        }

        // El objeto viewOptions se renderiza dentro de "Opciones de visualización"
        if (permission === "viewOptions") {
          return;
        }

        html += `
          <label class="flex items-center gap-2">
            <input
              type="checkbox"
              data-role="${roleKey}"
              data-permission="${permission}"
              ${role.permissions[permission] ? 'checked' : ''}
              class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"
            />
            <span class="text-xs text-slate-700">${permissionLabels[permission] || permission}</span>
          </label>
        `;

        if (permission === "create") {
          const ct = role.permissions.createTypes || {};
          html += `
            <div class="ml-6 mt-2 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100">
              <div class="text-xs font-semibold text-slate-700">Tipos permitidos al crear</div>
              <div class="mt-2 grid grid-cols-2 gap-2">
                ${[
                  { key: "daily_close", label: "Cierre diario" },
                  { key: "expense", label: "Gasto" },
                  { key: "income", label: "Ingreso" },
                  { key: "expense_refund", label: "Devolución" },
                ].map(t => `
                  <label class="flex items-center gap-2">
                    <input
                      type="checkbox"
                      data-role="${roleKey}"
                      data-create-type="${t.key}"
                      ${ct[t.key] ? "checked" : ""}
                      ${role.permissions.create ? "" : "disabled"}
                      class="rounded border-slate-300 text-brand-600 focus:ring-brand-200 disabled:opacity-50"
                    />
                    <span class="text-xs text-slate-700">${t.label}</span>
                  </label>
                `).join("")}
              </div>
              <div class="mt-2 text-xs text-slate-500">Si desmarcas todos, el rol no podrá crear registros.</div>
            </div>
          `;
        }

        // Renderizar viewOptions para el rol visor
        if (permission === "viewOptions") {
          const vo = role.permissions.viewOptions || {};
          html += `
            <div class="ml-6 mt-2 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-100">
              <div class="text-xs font-semibold text-slate-700">Opciones de visualización</div>
              <div class="mt-2 grid grid-cols-2 gap-2">
                ${[
                  { key: "viewIncomes", label: "Ver Ingresos" },
                  { key: "viewExpenses", label: "Ver Gastos" },
                  { key: "viewDailyCloses", label: "Ver Cierres Diarios" },
                  { key: "viewOrders", label: "Ver Pedidos" },
                  { key: "viewEmployees", label: "Ver Empleados" },
                  { key: "viewCashWithdrawn", label: "Ver Efectivo Retirado" },
                  { key: "viewCashControl", label: "Ver Control Efectivo" },
                  { key: "viewTrash", label: "Ver Papelera" },
                ].map(opt => `
                  <label class="flex items-center gap-2">
                    <input
                      type="checkbox"
                      data-role="${roleKey}"
                      data-view-option="${opt.key}"
                      ${vo[opt.key] ? "checked" : ""}
                      ${role.permissions.view ? "" : "disabled"}
                      class="rounded border-slate-300 text-brand-600 focus:ring-brand-200 disabled:opacity-50"
                    />
                    <span class="text-xs text-slate-700">${opt.label}</span>
                  </label>
                `).join("")}
              </div>
              <div class="mt-2 text-xs text-slate-500">Configura qué secciones puede visualizar este rol.</div>
            </div>
          `;
          return;
        }
      });

      html += '</div></div>';
    });

    html += '</div>';
    container.innerHTML = html;

    // Event listeners para checkboxes (NO guardar automáticamente, solo cuando se haga clic en Guardar)
    container.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      checkbox.addEventListener('change', (e) => {
        const roleKey = e.target.getAttribute('data-role');
        const permission = e.target.getAttribute('data-permission');
        const createType = e.target.getAttribute('data-create-type');
        const viewOption = e.target.getAttribute('data-view-option');
        const value = e.target.checked;

        if (ROLES[roleKey]) {
          if (createType) {
            // Toggle tipo de creación
            if (!ROLES[roleKey].permissions.createTypes) ROLES[roleKey].permissions.createTypes = {};
            ROLES[roleKey].permissions.createTypes[createType] = value;
            // create = al menos un tipo permitido
            ROLES[roleKey].permissions.create = Object.values(ROLES[roleKey].permissions.createTypes).some(Boolean);
            // NO guardar automáticamente, solo actualizar la UI
            // Re-render para reflejar disabled/enabled y evitar inconsistencia visual
            this.renderRolesEditor();
          } else if (viewOption) {
            // Toggle opción de visualización
            if (!ROLES[roleKey].permissions.viewOptions) ROLES[roleKey].permissions.viewOptions = {};
            ROLES[roleKey].permissions.viewOptions[viewOption] = value;
            // NO guardar automáticamente, solo actualizar la UI
            this.renderRolesEditor();
          } else if (permission) {
            ROLES[roleKey].permissions[permission] = value;

            // Si se apaga create, apagamos todos los tipos; si se enciende, dejamos al menos daily_close
            if (permission === "create") {
              if (!ROLES[roleKey].permissions.createTypes) ROLES[roleKey].permissions.createTypes = {};
              if (!value) {
                ROLES[roleKey].permissions.createTypes = {
                  daily_close: false,
                  expense: false,
                  income: false,
                  expense_refund: false,
                };
              } else {
                // Si estaba todo a false, habilitamos al menos cierre diario
                const any = Object.values(ROLES[roleKey].permissions.createTypes).some(Boolean);
                if (!any) ROLES[roleKey].permissions.createTypes.daily_close = true;
              }
            }

            // NO guardar automáticamente, solo actualizar la UI
            // Re-render para actualizar subpermisos de createTypes
            if (permission === "create") {
              this.renderRolesEditor();
            }
          }
        }
      });
    });
  }

  openUserModal(userId = null) {
    // Crear modal de usuario
    const modal = document.createElement('div');
    modal.id = 'userModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-slate-900/40 p-4 z-50';
    modal.innerHTML = `
      <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-soft">
        <div class="flex items-start justify-between gap-4 mb-4">
          <div>
            <div class="text-base font-semibold">${userId ? 'Editar usuario' : 'Añadir usuario'}</div>
            <div class="text-sm text-slate-500">Gestiona la información del usuario</div>
          </div>
          <button
            id="closeUserModalBtn"
            class="rounded-xl p-2 text-slate-500 hover:bg-slate-50"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form id="userForm" class="space-y-3">
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Nombre de usuario</span>
            <input
              id="userFormUsername"
              type="text"
              required
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            />
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Nombre completo</span>
            <input
              id="userFormName"
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            />
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">${userId ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña'}</span>
            <input
              id="userFormPassword"
              type="password"
              ${userId ? '' : 'required'}
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            />
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Rol</span>
            <select
              id="userFormRole"
              required
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-brand-200 focus:ring-4"
            >
              <option value="admin">Administrador</option>
              <option value="manager">Manager</option>
              <option value="empleado">Empleado</option>
              <option value="visor">Visor</option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs font-semibold text-slate-700">Tiendas asignadas</span>
            <div class="mt-1 rounded-xl border border-slate-200 bg-white p-3 max-h-48 overflow-y-auto">
              <label class="flex items-center gap-2 mb-2 pb-2 border-b border-slate-100">
                <input
                  type="checkbox"
                  id="userFormStoreAll"
                  class="rounded border-slate-300 text-brand-600 focus:ring-brand-200"
                />
                <span class="text-xs font-semibold text-slate-700">Todas las tiendas</span>
              </label>
              ${STORES.map(store => `
                <label class="flex items-center gap-2 mb-1">
                  <input
                    type="checkbox"
                    class="userFormStoreCheckbox rounded border-slate-300 text-brand-600 focus:ring-brand-200"
                    value="${store.id}"
                  />
                  <span class="text-xs text-slate-700">${store.name}</span>
                </label>
              `).join('')}
            </div>
            <div class="mt-1 text-xs text-slate-500">Si no se selecciona ninguna, el usuario tendrá acceso a todas las tiendas</div>
          </label>

          <div class="flex items-center justify-end gap-2 mt-4">
            <button
              type="button"
              id="cancelUserBtn"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
            >
              ${userId ? 'Guardar cambios' : 'Crear usuario'}
            </button>
          </div>
        </form>
      </div>
    `;

    document.body.appendChild(modal);

    // Llenar formulario si es edición
    if (userId) {
      const users = authManager.getAllUsers();
      const user = users.find(u => u.id === userId);
      if (user) {
        document.getElementById('userFormUsername').value = user.username;
        document.getElementById('userFormName').value = user.name || '';
        document.getElementById('userFormRole').value = user.role;
        
        // Manejar tiendas asignadas (compatibilidad hacia atrás)
        const assignedStores = user.assignedStores || (user.assignedStore ? [user.assignedStore] : null);
        if (!assignedStores || assignedStores.length === 0) {
          document.getElementById('userFormStoreAll').checked = true;
        } else {
          assignedStores.forEach(storeId => {
            const checkbox = document.querySelector(`.userFormStoreCheckbox[value="${storeId}"]`);
            if (checkbox) checkbox.checked = true;
          });
        }
      }
    }

    // Event listener para "Todas las tiendas"
    const storeAllCheckbox = document.getElementById('userFormStoreAll');
    const storeCheckboxes = document.querySelectorAll('.userFormStoreCheckbox');
    
    storeAllCheckbox.addEventListener('change', (e) => {
      if (e.target.checked) {
        storeCheckboxes.forEach(cb => cb.checked = false);
      }
    });
    
    storeCheckboxes.forEach(cb => {
      cb.addEventListener('change', () => {
        if (cb.checked) {
          storeAllCheckbox.checked = false;
        }
      });
    });

    // Event listeners
    document.getElementById('closeUserModalBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('cancelUserBtn').addEventListener('click', () => {
      modal.remove();
    });

    document.getElementById('userForm').addEventListener('submit', (e) => {
      e.preventDefault();
      this.handleUserFormSubmit(userId);
    });
  }

  handleUserFormSubmit(userId) {
    const formData = {
      username: document.getElementById('userFormUsername').value,
      name: document.getElementById('userFormName').value,
      password: document.getElementById('userFormPassword').value,
      role: document.getElementById('userFormRole').value,
      assignedStores: (() => {
        const storeAllCheckbox = document.getElementById('userFormStoreAll');
        if (storeAllCheckbox && storeAllCheckbox.checked) {
          return null; // null = todas las tiendas
        }
        const checkedStores = Array.from(document.querySelectorAll('.userFormStoreCheckbox:checked'))
          .map(cb => cb.value);
        return checkedStores.length > 0 ? checkedStores : null;
      })()
    };

    let result;
    if (userId) {
      // No actualizar contraseña si está vacía
      if (!formData.password) {
        delete formData.password;
      }
      result = authManager.updateUser(userId, formData);
    } else {
      result = authManager.createUser(formData);
    }

    if (result.success) {
      document.getElementById('userModal').remove();
      this.renderUsersTable();
    } else {
      alert(result.message);
    }
  }
}

// Instancia global del gestor de usuarios
let userManager;
