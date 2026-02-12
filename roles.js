// Sistema de roles y permisos
// Los permisos pueden ser modificados por el administrador
let ROLES = {
  admin: {
    level: 1,
    name: 'Administrador',
    description: 'Acceso completo a todas las funciones.',
    permissions: {
      view: true,
      create: true,
      // Tipos de registros permitidos al crear
      createTypes: {
        daily_close: true,
        expense: true,
        income: true,
        expense_refund: true,
      },
      edit: true,
      delete: true,
      export: true,
      settings: true,
      manageUsers: true,
      manageEmployees: true,
      manageOrders: true,
      viewOwnEmployee: true,
      viewOptions: {
        viewIncomes: true,
        viewExpenses: true,
        viewDailyCloses: true,
        viewOrders: true,
        viewEmployees: true,
        viewCashWithdrawn: true,
        viewCashControl: true,
        viewTrash: true,
      }
    }
  },
  manager: {
    level: 2,
    name: 'Manager',
    description: 'Puede registrar cierres, gastos e ingresos. Puede añadir empleados y pedidos.',
    permissions: {
      view: true,
      create: true,
      createTypes: {
        daily_close: true,
        expense: true,
        income: true,
        expense_refund: false,
      },
      edit: true,
      delete: false,
      export: false,
      settings: false,
      manageUsers: false,
      manageEmployees: true,
      manageOrders: true,
      viewOwnEmployee: true,
      viewOptions: {
        viewIncomes: true,
        viewExpenses: true,
        viewDailyCloses: true,
        viewOrders: true,
        viewEmployees: true,
        viewCashWithdrawn: true,
        viewCashControl: true,
        viewTrash: false,
      }
    }
  },
  empleado: {
    level: 3,
    name: 'Empleado',
    description: 'Solo puede añadir cierres diarios y ver su propia ficha de empleado.',
    permissions: {
      view: true,
      create: true,
      createTypes: {
        daily_close: true,
        expense: false,
        income: false,
        expense_refund: false,
      },
      edit: false,
      delete: false,
      export: false,
      settings: false,
      manageUsers: false,
      manageEmployees: false,
      manageOrders: false,
      viewOwnEmployee: true,
      viewOptions: {
        viewIncomes: false,
        viewExpenses: false,
        viewDailyCloses: true,
        viewOrders: false,
        viewEmployees: false,
        viewCashWithdrawn: false,
        viewCashControl: false,
        viewTrash: false,
      }
    }
  },
  visor: {
    level: 4,
    name: 'Visor',
    description: 'Solo puede visualizar los datos según las opciones configuradas.',
    permissions: {
      view: true,
      create: false,
      createTypes: {
        daily_close: false,
        expense: false,
        income: false,
        expense_refund: false,
      },
      edit: false,
      delete: false,
      export: false,
      settings: false,
      manageUsers: false,
      manageEmployees: false,
      manageOrders: false,
      viewOwnEmployee: false,
      viewOptions: {
        viewIncomes: true,
        viewExpenses: true,
        viewDailyCloses: true,
        viewOrders: true,
        viewEmployees: true,
        viewCashWithdrawn: true,
        viewCashControl: true,
        viewTrash: false,
      }
    }
  }
};

function normalizeRoleCreateTypes(roleKey) {
  const role = ROLES[roleKey];
  if (!role || !role.permissions) return;
  const p = role.permissions;

  // Migración: si no existe createTypes, lo creamos según create (boolean)
  if (!p.createTypes || typeof p.createTypes !== "object") {
    const allowAll = Boolean(p.create);
    p.createTypes = {
      daily_close: allowAll,
      expense: allowAll,
      income: allowAll,
      expense_refund: allowAll,
    };
  } else {
    // Asegurar todas las claves
    p.createTypes.daily_close = Boolean(p.createTypes.daily_close);
    p.createTypes.expense = Boolean(p.createTypes.expense);
    p.createTypes.income = Boolean(p.createTypes.income);
    p.createTypes.expense_refund = Boolean(p.createTypes.expense_refund);
  }

  // Mantener create como “tiene al menos un tipo permitido”
  p.create = Object.values(p.createTypes).some(Boolean);

  // Normalizar viewOptions si no existe
  if (!p.viewOptions || typeof p.viewOptions !== "object") {
    p.viewOptions = {
      viewIncomes: Boolean(p.view),
      viewExpenses: Boolean(p.view),
      viewDailyCloses: Boolean(p.view),
      viewOrders: Boolean(p.view),
      viewEmployees: Boolean(p.view),
      viewCashWithdrawn: Boolean(p.view),
      viewCashControl: Boolean(p.view),
      viewTrash: Boolean(p.view),
    };
  } else {
    // Asegurar todas las claves de viewOptions
    p.viewOptions.viewIncomes = Boolean(p.viewOptions.viewIncomes);
    p.viewOptions.viewExpenses = Boolean(p.viewOptions.viewExpenses);
    p.viewOptions.viewDailyCloses = Boolean(p.viewOptions.viewDailyCloses);
    p.viewOptions.viewOrders = Boolean(p.viewOptions.viewOrders);
    p.viewOptions.viewEmployees = Boolean(p.viewOptions.viewEmployees);
    p.viewOptions.viewCashWithdrawn = Boolean(p.viewOptions.viewCashWithdrawn);
    p.viewOptions.viewCashControl = Boolean(p.viewOptions.viewCashControl);
    p.viewOptions.viewTrash = Boolean(p.viewOptions.viewTrash);
  }

  // Normalizar nuevos permisos si no existen
  if (p.manageEmployees === undefined) p.manageEmployees = false;
  if (p.manageOrders === undefined) p.manageOrders = false;
  if (p.viewOwnEmployee === undefined) p.viewOwnEmployee = false;
}

// Guardar roles personalizados
function saveCustomRoles() {
  Object.keys(ROLES).forEach(normalizeRoleCreateTypes);
  localStorage.setItem('miramira_custom_roles', JSON.stringify(ROLES));
}

// Cargar roles personalizados
function loadCustomRoles() {
  const saved = localStorage.getItem('miramira_custom_roles');
  if (saved) {
    try {
      const customRoles = JSON.parse(saved);
      // Mantener la estructura pero actualizar permisos
      Object.keys(customRoles).forEach(roleKey => {
        if (ROLES[roleKey]) {
          ROLES[roleKey].permissions = customRoles[roleKey].permissions;
          normalizeRoleCreateTypes(roleKey);
        }
      });
    } catch (e) {
      console.error('Error cargando roles personalizados:', e);
    }
  }
}

// Cargar roles al inicio
loadCustomRoles();
Object.keys(ROLES).forEach(normalizeRoleCreateTypes);

// Gestor de roles
class RoleManager {
  constructor() {
    // Ya no carga el rol desde localStorage, ahora usa authManager
  }

  getCurrentRole() {
    if (typeof authManager !== 'undefined' && authManager.getCurrentUser()) {
      return authManager.getUserRole();
    }
    return null;
  }

  getCurrentRoleData() {
    const role = this.getCurrentRole();
    return role && ROLES[role] ? ROLES[role] : null;
  }

  hasPermission(permission) {
    if (typeof authManager !== 'undefined') {
      return authManager.hasPermission(permission);
    }
    return false;
  }

  // Mapeo tipo de registro -> permiso Laravel (modulo.submodulo.accion)
  static ENTRY_TYPE_PERMISSIONS = {
    daily_close: 'financial.daily_closes.create',
    expense: 'financial.expenses.create',
    income: 'financial.income.create',
    expense_refund: 'financial.registros.create',
  };

  canCreateType(entryType) {
    if (typeof window !== 'undefined' && window.LaravelAuth && window.LaravelAuth.permissions) {
      const perm = RoleManager.ENTRY_TYPE_PERMISSIONS[entryType] || 'financial.registros.create';
      return window.LaravelAuth.permissions[perm] === true;
    }
    const roleData = this.getCurrentRoleData();
    if (!roleData || !roleData.permissions) return false;
    const p = roleData.permissions;
    if (!p.createTypes || typeof p.createTypes !== "object") return Boolean(p.create);
    return Boolean(p.createTypes[entryType]);
  }

  getAllowedCreateTypes() {
    if (typeof window !== 'undefined' && window.LaravelAuth && window.LaravelAuth.permissions) {
      const perms = window.LaravelAuth.permissions;
      return Object.keys(RoleManager.ENTRY_TYPE_PERMISSIONS).filter(
        (type) => perms[RoleManager.ENTRY_TYPE_PERMISSIONS[type]] === true
      );
    }
    const roleData = this.getCurrentRoleData();
    if (!roleData || !roleData.permissions) return [];
    const p = roleData.permissions;
    const ct = p.createTypes || {};
    return Object.keys(ct).filter((k) => ct[k]);
  }

  applyRolePermissions() {
    const roleData = this.getCurrentRoleData();
    
    // Ocultar/mostrar botón "Añadir registro"
    const addEntryBtn = document.getElementById('addEntryBtn');
    if (addEntryBtn) {
      const canCreateAny = this.getAllowedCreateTypes().length > 0;
      if (canCreateAny) {
        addEntryBtn.style.display = 'inline-flex';
        addEntryBtn.disabled = false;
      } else {
        addEntryBtn.style.display = 'none';
      }
    }

    // Ocultar/mostrar botón "Exportar CSV"
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
      if (this.hasPermission('export')) {
        exportBtn.style.display = 'block';
        exportBtn.disabled = false;
      } else {
        exportBtn.style.display = 'none';
      }
    }

    // Ocultar/mostrar botones de edición en la tabla
    const editButtons = document.querySelectorAll('[data-action="edit"]');
    editButtons.forEach(btn => {
      if (this.hasPermission('financial.registros.edit')) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
      } else {
        btn.style.display = 'none';
      }
    });

    // Ocultar/mostrar botones de eliminación en la tabla
    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    deleteButtons.forEach(btn => {
      if (this.hasPermission('delete')) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
      } else {
        btn.style.display = 'none';
      }
    });

    // Deshabilitar campos del formulario según permisos
    this.updateFormPermissions();

    // Actualizar descripción del rol
    const roleDescription = document.getElementById('roleDescription');
    if (roleDescription) {
      roleDescription.textContent = roleData.description;
    }
  }

  updateFormPermissions() {
    const form = document.getElementById('entryForm');
    if (!form) return;

    const canEdit = this.hasPermission('financial.registros.edit');
    const canCreate = this.hasPermission('financial.registros.create');

    // Si no puede crear, no mostrar el modal
    if (!canCreate) {
      const modal = document.getElementById('entryModal');
      if (modal) {
        modal.style.display = 'none';
      }
    }

    // Si está en modo edición y no puede editar, deshabilitar campos
    const entryId = document.getElementById('entryId');
    const isEditMode = entryId && entryId.value;

    if (isEditMode && !canEdit) {
      const formInputs = form.querySelectorAll('input, select, textarea, button[type="submit"]');
      formInputs.forEach(input => {
        if (input.type !== 'hidden') {
          input.disabled = true;
        }
      });
    } else if (!isEditMode && !canCreate) {
      // Si no puede crear, deshabilitar todos los campos
      const formInputs = form.querySelectorAll('input, select, textarea, button[type="submit"]');
      formInputs.forEach(input => {
        if (input.type !== 'hidden') {
          input.disabled = true;
        }
      });
    } else {
      // Habilitar campos si tiene permisos
      const formInputs = form.querySelectorAll('input, select, textarea, button[type="submit"]');
      formInputs.forEach(input => {
        input.disabled = false;
      });
    }
  }

  // Método para actualizar permisos después de renderizar la tabla
  updateTablePermissions() {
    const editButtons = document.querySelectorAll('[data-action="edit"]');
    editButtons.forEach(btn => {
      if (this.hasPermission('financial.registros.edit')) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
      } else {
        btn.style.display = 'none';
      }
    });

    const deleteButtons = document.querySelectorAll('[data-action="delete"]');
    deleteButtons.forEach(btn => {
      if (this.hasPermission('financial.registros.delete')) {
        btn.style.display = 'inline-flex';
        btn.disabled = false;
      } else {
        btn.style.display = 'none';
      }
    });
  }
}

// Instancia global del gestor de roles
// Se inicializa en app.js después de la autenticación
let roleManager;
