<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="/logo.png" alt="Salle de Sport" class="logo" />
        <h1>Connexion</h1>
        <p>Système de Gestion de Salle de Sport</p>
      </div>
      
      <div class="login-body">
        <div class="alert alert-danger" v-if="error">
          {{ error }}
        </div>
        
        <form @submit.prevent="login">
          <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-envelope"></i>
              </span>
              <input
                type="email"
                id="email"
                v-model="email"
                class="form-control"
                placeholder="Entrez votre adresse e-mail"
                required
                autocomplete="email"
              />
            </div>
          </div>
          
          <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-lock"></i>
              </span>
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="password"
                class="form-control"
                placeholder="Entrez votre mot de passe"
                required
                autocomplete="current-password"
              />
              <button 
                type="button" 
                class="btn btn-outline-secondary" 
                @click="togglePasswordVisibility"
              >
                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
              </button>
            </div>
          </div>
          
          <div class="form-check">
            <input
              type="checkbox"
              id="remember"
              v-model="remember"
              class="form-check-input"
            />
            <label for="remember" class="form-check-label">
              Se souvenir de moi
            </label>
          </div>
          
          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Connexion
          </button>
        </form>
      </div>
      
      <div class="login-footer">
        <p>&copy; {{ new Date().getFullYear() }} - Système de Gestion de Salle de Sport</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '@/config/api';

const router = useRouter();
const authStore = useAuthStore();

// Pour faire des requêtes
export default {
  setup() {
    const members = ref([]);

    const fetchMembers = async () => {
      try {
        const response = await api.get('/members');
        members.value = response.data.data;
      } catch (error) {
        console.error('Erreur lors du chargement des membres :', error);
      }
    };

    onMounted(() => {
      fetchMembers();
    });

    return {
      members,
    };
  }
};

// États
const email = ref('');
const password = ref('');
const remember = ref(false);
const error = ref('');
const loading = ref(false);
const showPassword = ref(false);

// Fonction de connexion
const login = async () => {
  try {
    error.value = '';
    loading.value = true;
    
    const success = await authStore.login({
      email: email.value,
      password: password.value,
      remember: remember.value
    });
    
    if (success) {
      router.push('/');
    }
  } catch (err) {
    if (err.response && err.response.data) {
      error.value = err.response.data.message || 'Erreur lors de la connexion';
    } else {
      error.value = 'Erreur de connexion au serveur';
    }
  } finally {
    loading.value = false;
  }
};

// Basculer la visibilité du mot de passe
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 420px;
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.login-header {
  background-color: #343a40;
  color: #fff;
  padding: 30px 20px;
  text-align: center;
}

.logo {
  max-width: 150px;
  margin-bottom: 20px;
}

.login-header h1 {
  font-size: 24px;
  margin: 0 0 10px;
}

.login-header p {
  margin: 0;
  opacity: 0.8;
}

.login-body {
  padding: 30px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
}

.input-group-text {
  background-color: #f8f9fa;
}

.form-check {
  margin-bottom: 20px;
}

.btn-block {
  display: block;
  width: 100%;
  padding: 12px;
  font-size: 16px;
}

.login-footer {
  padding: 15px;
  text-align: center;
  background-color: #f8f9fa;
  border-top: 1px solid #eee;
  font-size: 12px;
  color: #6c757d;
}
</style>