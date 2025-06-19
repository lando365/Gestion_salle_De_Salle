<template>
  <div class="members-list">
    <div class="page-header">
      <h1>Gestion des Membres</h1>
      <router-link to="/members/create" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Ajouter un membre
      </router-link>
    </div>

    <!-- Filtres et recherche -->
    <div class="filters-container">
      <div class="row">
        <div class="col-md-6 col-lg-3">
          <div class="form-group">
            <input 
              type="text" 
              v-model="filters.search" 
              class="form-control" 
              placeholder="Rechercher..."
              @input="debouncedSearch"
            >
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="form-group">
            <select v-model="filters.status" class="form-select" @change="fetchMembers">
              <option value="">Tous les statuts</option>
              <option value="active">Actif</option>
              <option value="inactive">Inactif</option>
              <option value="pending">En attente</option>
            </select>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="form-group">
            <select v-model="filters.sort_field" class="form-select" @change="fetchMembers">
              <option value="created_at">Date d'inscription</option>
              <option value="last_name">Nom</option>
              <option value="first_name">Prénom</option>
              <option value="email">Email</option>
              <option value="status">Statut</option>
            </select>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="form-group">
            <select v-model="filters.sort_direction" class="form-select" @change="fetchMembers">
              <option value="desc">Décroissant</option>
              <option value="asc">Croissant</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Tableau des membres -->
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Statut</th>
            <th>Abonnement</th>
            <th>Date d'inscription</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody v-if="loading">
          <tr>
            <td colspan="7" class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
              </div>
            </td>
          </tr>
        </tbody>
        <tbody v-else-if="members.length === 0">
          <tr>
            <td colspan="7" class="text-center">
              Aucun membre trouvé
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr v-for="member in members" :key="member.id">
            <td>
              <router-link :to="`/members/${member.id}`" class="member-name">
                {{ member.first_name }} {{ member.last_name }}
              </router-link>
            </td>
            <td>{{ member.email }}</td>
            <td>{{ member.phone }}</td>
            <td>
              <span class="badge" :class="getStatusClass(member.status)">
                {{ getStatusLabel(member.status) }}
              </span>
            </td>
            <td>
              <span v-if="member.active_subscription">
                {{ member.active_subscription.name }}
                <br>
                <small>Expire le {{ formatDate(member.active_subscription.end_date) }}</small>
              </span>
              <span v-else class="text-muted">Aucun abonnement actif</span>
            </td>
            <td>{{ formatDate(member.created_at) }}</td>
            <td>
              <div class="btn-group">
                <router-link :to="`/members/${member.id}`" class="btn btn-sm btn-outline-info">
                  <i class="bi bi-eye"></i>
                </router-link>
                <router-link :to="`/members/${member.id}/edit`" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i>
                </router-link>
                <button 
                  @click="confirmDelete(member)"
                  class="btn btn-sm btn-outline-danger"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="pagination.last_page > 1">
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(1)">
              <i class="bi bi-chevron-double-left"></i>
            </a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          
          <li 
            v-for="page in paginationPages" 
            :key="page" 
            class="page-item"
            :class="{ active: pagination.current_page === page }"
          >
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          
          <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="changePage(pagination.last_page)">
              <i class="bi bi-chevron-double-right"></i>
            </a>
          </li>
        </ul>
      </nav>
      
      <div class="per-page-selector">
        <select v-model="filters.per_page" class="form-select" @change="fetchMembers">
          <option :value="10">10 par page</option>
          <option :value="25">25 par page</option>
          <option :value="50">50 par page</option>
          <option :value="100">100 par page</option>
        </select>
      </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmer la suppression</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="memberToDelete">
            <p>Êtes-vous sûr de vouloir supprimer le membre <strong>{{ memberToDelete.first_name }} {{ memberToDelete.last_name }}</strong> ?</p>
            <p class="text-danger">Cette action est irréversible.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" class="btn btn-danger" @click="deleteMember">Supprimer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';
import debounce from 'lodash/debounce';
import api from '@/config/api';

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

// État
const members = ref([]);
const loading = ref(true);
const memberToDelete = ref(null);
const deleteModal = ref(null);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Filtres
const filters = ref({
  search: '',
  status: '',
  sort_field: 'created_at',
  sort_direction: 'desc',
  page: 1,
  per_page: 10,
});

// Computed
const paginationPages = computed(() => {
  const range = [];
  const currentPage = pagination.value.current_page;
  const lastPage = pagination.value.last_page;
  
  // Afficher 5 pages maximum
  const maxPages = 5;
  
  // Calculer la plage de pages à afficher
  let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
  let endPage = Math.min(lastPage, startPage + maxPages - 1);
  
  // Ajuster si on est proche de la dernière page
  if (endPage - startPage + 1 < maxPages) {
    startPage = Math.max(1, endPage - maxPages + 1);
  }
  
  for (let i = startPage; i <= endPage; i++) {
    range.push(i);
  }
  
  return range;
});

// Debounce pour la recherche
const debouncedSearch = debounce(() => {
  filters.value.page = 1; // Revenir à la première page
  fetchMembers();
}, 300);

// Charger les membres
const fetchMembers = async () => {
  try {
    loading.value = true;
    
    const response = await axios.get('/api/members', {
      params: filters.value,
    });
    
    if (response.data.success) {
      members.value = response.data.data.data;
      
      // Mettre à jour la pagination
      pagination.value = {
        current_page: response.data.data.current_page,
        last_page: response.data.data.last_page,
        total: response.data.data.total,
      };
    }
  } catch (error) {
    console.error('Erreur lors du chargement des membres:', error);
  } finally {
    loading.value = false;
  }
};

// Changer de page
const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) {
    return;
  }
  
  filters.value.page = page;
  fetchMembers();
};

// Classes CSS pour les statuts
const getStatusClass = (status) => {
  const statusMap = {
    'active': 'bg-success',
    'inactive': 'bg-danger',
    'pending': 'bg-warning'
  };
  
  return statusMap[status] || 'bg-secondary';
};

// Labels pour les statuts
const getStatusLabel = (status) => {
  const statusMap = {
    'active': 'Actif',
    'inactive': 'Inactif',
    'pending': 'En attente'
  };
  
  return statusMap[status] || status;
};

// Formatage de la date
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};

// Confirmer la suppression
const confirmDelete = (member) => {
  memberToDelete.value = member;
  deleteModal.value.show();
};

// Supprimer un membre
const deleteMember = async () => {
  if (!memberToDelete.value) {
    return;
  }
  
  try {
    const response = await axios.delete(`/api/members/${memberToDelete.value.id}`);
    
    if (response.data.success) {
      // Supprimer de la liste locale
      members.value = members.value.filter(m => m.id !== memberToDelete.value.id);
      
      // Afficher un message de succès
      alert('Membre supprimé avec succès');
      
      // Fermer la modal
      deleteModal.value.hide();
      
      // Recharger les données si nécessaire
      if (members.value.length === 0 && pagination.value.current_page > 1) {
        changePage(pagination.value.current_page - 1);
      }
    }
  } catch (error) {
    console.error('Erreur lors de la suppression du membre:', error);
    alert('Erreur lors de la suppression du membre');
  }
};

// Initialisation
onMounted(() => {
  // Initialiser la modal de confirmation
  deleteModal.value = new Modal(document.getElementById('deleteModal'));
  
  // Charger les membres
  fetchMembers();
});
</script>

<style scoped>
.members-list {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  padding: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  margin: 0;
  font-size: 1.5rem;
}

.filters-container {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.member-name {
  font-weight: 500;
  color: #007bff;
  text-decoration: none;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
}

.per-page-selector {
  width: 150px;
}

.table {
  margin-bottom: 0;
}

.table th {
  font-weight: 600;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}

.btn-group {
  display: flex;
  gap: 0.25rem;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .per-page-selector {
    margin-top: 1rem;
  }
}
</style>