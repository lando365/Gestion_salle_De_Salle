<template>
  <div class="dashboard">
    <!-- Cartes de statistiques -->
    <div class="stats-cards">
      <div class="card" v-for="(stat, index) in statsCards" :key="index">
        <div class="card-body">
          <div class="card-icon" :style="{ backgroundColor: stat.color }">
            <i class="bi" :class="stat.icon"></i>
          </div>
          <div class="card-content">
            <h5 class="card-title">{{ stat.title }}</h5>
            <h2 class="card-value">{{ formatValue(stat.value, stat.format) }}</h2>
            <p class="card-description" v-if="stat.description">
              {{ stat.description }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-row">
      <div class="chart-card">
        <div class="card-header">
          <h5 class="chart-title">Réservations par jour</h5>
        </div>
        <div class="card-body">
          <canvas ref="reservationsChart"></canvas>
        </div>
      </div>
      
      <div class="chart-card">
        <div class="card-header">
          <h5 class="chart-title">Services populaires</h5>
        </div>
        <div class="card-body">
          <canvas ref="servicesChart"></canvas>
        </div>
      </div>
    </div>
    
    <!-- Réservations à venir -->
    <div class="upcoming-reservations">
      <div class="card-header">
        <h5>Réservations à venir</h5>
        <router-link to="/reservations" class="view-all">Voir tout</router-link>
      </div>
      <div class="card-body">
        <div v-if="loadingReservations" class="loading">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>
        <div v-else-if="upcomingReservations.length === 0" class="no-data">
          Aucune réservation à venir
        </div>
        <div v-else class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Membre</th>
                <th>Service</th>
                <th>Coach</th>
                <th>Date & Heure</th>
                <th>Statut</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="reservation in upcomingReservations" :key="reservation.id">
                <td>{{ reservation.member.full_name }}</td>
                <td>{{ reservation.service.name }}</td>
                <td>{{ reservation.coach ? reservation.coach.name : 'N/A' }}</td>
                <td>
                  {{ formatDate(reservation.start_time) }}<br>
                  <small>{{ formatTime(reservation.start_time) }} - {{ formatTime(reservation.end_time) }}</small>
                </td>
                <td>
                  <span class="badge" :class="getStatusClass(reservation.status)">
                    {{ getStatusLabel(reservation.status) }}
                  </span>
                </td>
                <td>
                  <router-link :to="`/reservations/${reservation.id}/edit`" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Chart from 'chart.js/auto';
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

// Store
const authStore = useAuthStore();

// Refs pour les graphiques
const reservationsChart = ref(null);
const servicesChart = ref(null);

// États
const stats = ref({});
const upcomingReservations = ref([]);
const loadingStats = ref(true);
const loadingReservations = ref(true);

// Stats cards
const statsCards = computed(() => {
  if (loadingStats.value || !stats.value) return [];
  
  const userRole = authStore.user.role;
  
  // Cartes de stats communes
  const cards = [
    {
      title: 'Membres actifs',
      value: stats.value.members?.active || 0,
      icon: 'bi-people-fill',
      color: '#4CAF50',
    },
    {
      title: 'Réservations',
      value: stats.value.reservations?.upcoming || 0,
      description: 'À venir',
      icon: 'bi-calendar-check-fill',
      color: '#2196F3',
    },
  ];
  
  // Ajouter stats spécifiques par rôle
  if (['admin', 'manager'].includes(userRole)) {
    cards.push({
      title: 'Revenus ce mois',
      value: stats.value.finances?.revenue_this_month || 0,
      format: 'currency',
      icon: 'bi-cash',
      color: '#9C27B0',
    });
    
    cards.push({
      title: 'Nouveaux membres',
      value: stats.value.members?.new_this_month || 0,
      description: 'Ce mois-ci',
      icon: 'bi-person-plus-fill',
      color: '#FF9800',
    });
  }
  
  if (userRole === 'coach') {
    cards.push({
      title: 'Mes sessions',
      value: stats.value.reservations?.completed || 0,
      description: 'Terminées',
      icon: 'bi-trophy-fill',
      color: '#9C27B0',
    });
    
    cards.push({
      title: 'Taux de présence',
      value: stats.value.reservations?.attendance_rate || 0,
      format: 'percent',
      icon: 'bi-bar-chart-fill',
      color: '#FF9800',
    });
  }
  
  return cards;
});

// Chargement des statistiques du tableau de bord
const loadDashboardStats = async () => {
  try {
    loadingStats.value = true;
    const response = await axios.get('/api/dashboard/stats');
    
    if (response.data.success) {
      stats.value = response.data.data;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error);
  } finally {
    loadingStats.value = false;
  }
};

// Chargement des réservations à venir
const loadUpcomingReservations = async () => {
  try {
    loadingReservations.value = true;
    const response = await axios.get('/api/upcoming-reservations');
    
    if (response.data.success) {
      upcomingReservations.value = response.data.data;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des réservations:', error);
  } finally {
    loadingReservations.value = false;
  }
};

// Initialisation des graphiques
const initCharts = () => {
  if (!stats.value) return;
  
  // Graphique des réservations par jour
  if (stats.value.reservations_by_day) {
    const ctx = reservationsChart.value.getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: stats.value.reservations_by_day.map(item => item.day),
        datasets: [{
          label: 'Réservations',
          data: stats.value.reservations_by_day.map(item => item.count),
          backgroundColor: '#2196F3',
          borderColor: '#1E88E5',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }
  
  // Graphique des services populaires
  if (stats.value.popular_services) {
    const ctx = servicesChart.value.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: stats.value.popular_services.map(item => item.service),
        datasets: [{
          data: stats.value.popular_services.map(item => item.count),
          backgroundColor: [
            '#4CAF50',
            '#2196F3',
            '#9C27B0',
            '#FF9800',
            '#F44336'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }
};

// Formatage des valeurs
const formatValue = (value, format) => {
  if (format === 'currency') {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
  } else if (format === 'percent') {
    return new Intl.NumberFormat('fr-FR', { style: 'percent', maximumFractionDigits: 1 }).format(value / 100);
  } else {
    return value;
  }
};

// Formatage de la date
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};

// Formatage de l'heure
const formatTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

// Classes CSS pour les statuts
const getStatusClass = (status) => {
  const statusMap = {
    'scheduled': 'bg-primary',
    'completed': 'bg-success',
    'cancelled': 'bg-danger',
    'no_show': 'bg-warning'
  };
  
  return statusMap[status] || 'bg-secondary';
};

// Labels pour les statuts
const getStatusLabel = (status) => {
  const statusMap = {
    'scheduled': 'Planifié',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'no_show': 'Absent'
  };
  
  return statusMap[status] || status;
};

// Au montage du composant
onMounted(async () => {
  await Promise.all([
    loadDashboardStats(),
    loadUpcomingReservations()
  ]);
  
  // Initialiser les graphiques une fois les données chargées
  initCharts();
});
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.stats-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-body {
  padding: 20px;
  display: flex;
  align-items: center;
}

.card-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
}

.card-icon i {
  font-size: 24px;
  color: #fff;
}

.card-content {
  flex: 1;
}

.card-title {
  font-size: 14px;
  color: #6c757d;
  margin-bottom: 5px;
}

.card-value {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
}

.card-description {
  font-size: 12px;
  color: #6c757d;
  margin-top: 5px;
}

.charts-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.chart-card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
}

.chart-title {
  font-size: 16px;
  margin: 0;
}

.upcoming-reservations {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.view-all {
  font-size: 14px;
  color: #007bff;
  text-decoration: none;
}

.table {
  margin-bottom: 0;
}

.table th {
  font-weight: 600;
}

.loading, .no-data {
  padding: 30px;
  text-align: center;
  color: #6c757d;
}

.badge {
  font-size: 12px;
  padding: 5px 8px;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .stats-cards {
    grid-template-columns: 1fr;
  }
  
  .charts-row {
    grid-template-columns: 1fr;
  }
}
</style>