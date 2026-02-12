// Sistema de autenticación
const USERS_STORAGE_KEY = 'miramira_users';
const CURRENT_USER_KEY = 'miramira_current_user';

class AuthManager {
  constructor() {
    try {
      this.users = this.loadUsers();
      this.currentUser = this.loadCurrentUser();
      
      // Asegurar que this.users sea un array
      if (!Array.isArray(this.users)) {
        console.warn('this.users no es un array, inicializando como array vacío');
        this.users = [];
      }
      
      // Asegurar que siempre exista un usuario admin
      if (!this.users || this.users.length === 0 || !this.users.find(u => u.username === 'admin')) {
        console.log("Creando usuario admin por defecto...");
        this.createDefaultAdmin();
      }
      
      console.log('AuthManager inicializado. Total usuarios:', this.users.length);
      console.log('Usuarios disponibles:', this.users.map(u => u.username));
    } catch (error) {
      console.error("Error en constructor de AuthManager:", error);
      this.users = [];
      this.currentUser = null;
      this.createDefaultAdmin();
    }
  }

  loadUsers() {
    try {
      const stored = localStorage.getItem(USERS_STORAGE_KEY);
      if (!stored) return [];
      const parsed = JSON.parse(stored);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      console.error("Error al cargar usuarios desde localStorage:", error);
      // Si hay un error, limpiar y empezar de nuevo
      localStorage.removeItem(USERS_STORAGE_KEY);
      return [];
    }
  }

  saveUsers() {
    localStorage.setItem(USERS_STORAGE_KEY, JSON.stringify(this.users));
  }

  loadCurrentUser() {
    try {
      const stored = localStorage.getItem(CURRENT_USER_KEY);
      if (!stored) return null;
      return JSON.parse(stored);
    } catch (error) {
      console.error("Error al cargar usuario actual desde localStorage:", error);
      localStorage.removeItem(CURRENT_USER_KEY);
      return null;
    }
  }

  saveCurrentUser(user) {
    if (user) {
      localStorage.setItem(CURRENT_USER_KEY, JSON.stringify(user));
      this.currentUser = user;
    } else {
      localStorage.removeItem(CURRENT_USER_KEY);
      this.currentUser = null;
    }
  }

  createDefaultAdmin() {
    // Verificar que no exista ya un usuario admin
    if (this.users && this.users.find(u => u.username === 'admin')) {
      console.log('Usuario admin ya existe');
      return;
    }
    
    // Asegurar que this.users sea un array
    if (!Array.isArray(this.users)) {
      this.users = [];
    }
    
    const adminUser = {
      id: 'admin-' + Date.now(),
      username: 'admin',
      password: this.hashPassword('admin123'), // Contraseña por defecto
      role: 'admin',
      assignedStores: null, // null = acceso a todas las tiendas, array = tiendas específicas
      name: 'Administrador',
      createdAt: new Date().toISOString()
    };
    this.users.push(adminUser);
    this.saveUsers();
    console.log('Usuario admin por defecto creado. Usuario:', adminUser.username, 'Contraseña: admin123');
  }

  hashPassword(password) {
    // Hash simple (en producción usar bcrypt o similar)
    // Esto es solo para demo
    if (!password || typeof password !== 'string') {
      return '';
    }
    let hash = 0;
    for (let i = 0; i < password.length; i++) {
      const char = password.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash | 0; // Convert to 32bit integer
    }
    return hash.toString();
  }

  login(username, password) {
    if (!username || !password) {
      return { success: false, message: 'Usuario y contraseña son requeridos' };
    }

    const user = this.users.find(u => u.username === username);
    if (!user) {
      console.log('Usuario no encontrado. Usuarios disponibles:', this.users.map(u => u.username));
      return { success: false, message: 'Usuario no encontrado' };
    }

    const hashedPassword = this.hashPassword(password);
    if (user.password !== hashedPassword) {
      console.log('Contraseña incorrecta. Hash esperado:', user.password, 'Hash recibido:', hashedPassword);
      return { success: false, message: 'Contraseña incorrecta' };
    }

    this.saveCurrentUser(user);
    return { success: true, user };
  }

  logout() {
    this.saveCurrentUser(null);
  }

  isAuthenticated() {
    return this.currentUser !== null;
  }

  getCurrentUser() {
    return this.currentUser;
  }

  getUserRole() {
    return this.currentUser ? this.currentUser.role : null;
  }

  getAssignedStores() {
    return this.currentUser ? this.currentUser.assignedStores : null;
  }

  // Compatibilidad hacia atrás: obtener una sola tienda asignada (primera del array)
  getAssignedStore() {
    const stores = this.getAssignedStores();
    if (!stores || stores.length === 0) return null;
    if (Array.isArray(stores)) return stores[0];
    return stores; // Compatibilidad: si es string, devolverlo
  }

  hasPermission(permission) {
    // Prioridad: permisos inyectados por Laravel (modulo.submodulo.accion)
    if (typeof window !== 'undefined' && window.LaravelAuth && window.LaravelAuth.permissions) {
      return window.LaravelAuth.permissions[permission] === true;
    }
    if (!this.currentUser) return false;
    const role = this.currentUser.role;
    if (typeof ROLES === 'undefined' || !ROLES[role]) return false;
    return ROLES[role].permissions[permission] === true;
  }

  canAccessStore(storeId) {
    const assignedStores = this.getAssignedStores();
    // Si no tiene tiendas asignadas (null), puede acceder a todas
    if (!assignedStores) return true;
    // Si es un array, verificar si la tienda está en el array
    if (Array.isArray(assignedStores)) {
      return assignedStores.includes(storeId);
    }
    // Compatibilidad hacia atrás: si es string, comparar directamente
    return assignedStores === storeId;
  }

  // Gestión de usuarios (solo admin)
  createUser(userData) {
    if (!this.hasPermission('manageUsers')) {
      return { success: false, message: 'No tienes permisos para crear usuarios' };
    }

    // Verificar que el username no exista
    if (this.users.find(u => u.username === userData.username)) {
      return { success: false, message: 'El nombre de usuario ya existe' };
    }

    const newUser = {
      id: 'user-' + Date.now(),
      username: userData.username,
      password: this.hashPassword(userData.password),
      role: userData.role,
      assignedStores: userData.assignedStores || null,
      name: userData.name || userData.username,
      createdAt: new Date().toISOString()
    };

    this.users.push(newUser);
    this.saveUsers();
    return { success: true, user: newUser };
  }

  updateUser(userId, userData) {
    if (!this.hasPermission('admin.users.edit')) {
      return { success: false, message: 'No tienes permisos para editar usuarios' };
    }

    const userIndex = this.users.findIndex(u => u.id === userId);
    if (userIndex === -1) {
      return { success: false, message: 'Usuario no encontrado' };
    }

    // Verificar que el username no esté en uso por otro usuario
    if (userData.username && userData.username !== this.users[userIndex].username) {
      if (this.users.find(u => u.username === userData.username && u.id !== userId)) {
        return { success: false, message: 'El nombre de usuario ya existe' };
      }
    }

    // Actualizar datos
    if (userData.username) this.users[userIndex].username = userData.username;
    if (userData.password) {
      this.users[userIndex].password = this.hashPassword(userData.password);
    }
    if (userData.role) this.users[userIndex].role = userData.role;
    if (userData.assignedStores !== undefined) this.users[userIndex].assignedStores = userData.assignedStores;
    if (userData.name) this.users[userIndex].name = userData.name;

    // Si se está editando el usuario actual, actualizar la sesión
    if (this.currentUser && this.currentUser.id === userId) {
      this.saveCurrentUser(this.users[userIndex]);
    }

    this.saveUsers();
    return { success: true, user: this.users[userIndex] };
  }

  deleteUser(userId) {
    if (!this.hasPermission('manageUsers')) {
      return { success: false, message: 'No tienes permisos para eliminar usuarios' };
    }

    // No permitir eliminar el usuario actual
    if (this.currentUser && this.currentUser.id === userId) {
      return { success: false, message: 'No puedes eliminar tu propio usuario' };
    }

    const userIndex = this.users.findIndex(u => u.id === userId);
    if (userIndex === -1) {
      return { success: false, message: 'Usuario no encontrado' };
    }

    this.users.splice(userIndex, 1);
    this.saveUsers();
    return { success: true };
  }

  getAllUsers() {
    if (!this.hasPermission('admin.users.view')) {
      return [];
    }
    return this.users.map(u => ({
      ...u,
      password: undefined // No devolver la contraseña
    }));
  }
}

// Instancia global del gestor de autenticación
let authManager;

// Función de inicialización
function initializeAuthManager() {
  try {
    authManager = new AuthManager();
    console.log("AuthManager inicializado correctamente");
    console.log("Total de usuarios:", authManager.users.length);
    
    // Verificar si existe el usuario admin
    const adminUser = authManager.users.find(u => u.username === 'admin');
    if (adminUser) {
      console.log("Usuario admin encontrado");
    } else {
      console.log("Usuario admin no encontrado, creando uno nuevo...");
      authManager.createDefaultAdmin();
    }
  } catch (error) {
    console.error("Error al inicializar AuthManager:", error);
  }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    initializeAuthManager();
  });
} else {
  initializeAuthManager();
}
