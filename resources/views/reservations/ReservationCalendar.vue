<template>
  <div class="calendar-container">
    <div class="page-header">
      <h1>Calendrier des Réservations</h1>
      <div class="calendar-actions">
        <button class="btn btn-outline-primary" @click="today">Aujourd'hui</button>
        <div class="btn-group">
          <button class="btn btn-outline-secondary" @click="prev">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button class="btn btn-outline-secondary" @click="next">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="btn-group">
          <button 
            class="btn btn-outline-secondary" 
            :class="{ active: currentView === 'dayGridMonth' }"
            @click="changeView('dayGridMonth')"
          >
            Mois
          </button>
          <button 
            class="btn btn-outline-secondary" 
            :class="{ active: currentView === 'timeGridWeek' }"
            @click="changeView('timeGridWeek')"
          >
            Semaine
          </button>
          <button 
            class="btn btn-outline-secondary" 
            :class="{ active: currentView === 'timeGridDay' }"
            @click="changeView('timeGridDay')"
          >
            Jour
          </button>
        </div>
        <router-link to="/reservations/create" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Nouvelle réservation
        </router-link>
      </div>
    </div>
    
    <div class="calendar-wrapper">
      <div class="current-date">{{ formatCurrentDate() }}</div>
      <div ref="calendarEl" class="calendar"></div>
    </div>
    
    <!-- Modal détails de réservation -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails de la réservation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body" v-if="selectedEvent">
            <div class="event-status" :class="getStatusClass(selectedEvent.extendedProps.status)">
              {{ getStatusLabel(selectedEvent.extendedProps.status) }}
            </div>
            
            <div class="event-info">
              <div class="info-row">
                <div class="info-label">Membre:</div>
                <div class="info-value">{{ selectedEvent.extendedProps.member.first_name }} {{ selectedEvent.extendedProps.member.last_name }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Service:</div>
                <div class="info-value">{{ selectedEvent.extendedProps.service.name }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Coach:</div>
                <div class="info-value">{{ selectedEvent.extendedProps.coach ? selectedEvent.extendedProps.coach.name : 'Non assigné' }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Date:</div>
                <div class="info-value">{{ formatDate(selectedEvent.start) }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Horaire:</div>
                <div class="info-value">{{ formatTime(selectedEvent.start) }} - {{ formatTime(selectedEvent.end) }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            <router-link 
              :to="`/reservations/${selectedEvent?.id}/edit`" 
              class="btn btn-primary"
              @click="closeModal"
            >
              Modifier
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import frLocale from '@fullcalendar/core/locales/fr';
import { Modal } from 'bootstrap';
import axios from 'axios';
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

// Refs
const calendarEl = ref(null);
const calendar = ref(null);
const eventModal = ref(null);
const selectedEvent = ref(null);
const currentView = ref('dayGridMonth');

// Initialiser le calendrier
const initCalendar = () => {
  calendar.value = new Calendar(calendarEl.value, {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: currentView.value,
    locale: frLocale,
    headerToolbar: false, // On gère nous-mêmes la navigation
    editable: false,
    selectable: false,
    selectMirror: true,
    dayMaxEvents: true,
    weekNumbers: true,
    slotMinTime: '07:00:00',
    slotMaxTime: '22:00:00',
    businessHours: {
      daysOfWeek: [1, 2, 3, 4, 5, 6], // Lun-Sam
      startTime: '08:00',
      endTime: '21:00',
    },
    nowIndicator: true,
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: false,
      hour12: false
    },
    events: fetchEvents,
    eventClick: handleEventClick,
  });
  
  calendar.value.render();
};

// Charger les événements depuis l'API
const fetchEvents = (info, successCallback, failureCallback) => {
  const params = {
    start: info.startStr,
    end: info.endStr
  };
  
  axios.get('/api/calendar-events', { params })
    .then(response => {
      successCallback(response.data);
    })
    .catch(error => {
      console.error('Erreur lors du chargement des événements:', error);
      failureCallback(error);
    });
};

// Gestionnaire de clic sur un événement
const handleEventClick = (info) => {
  selectedEvent.value = {
    ...info.event,
    id: info.event.id,
    extendedProps: info.event.extendedProps
  };
  
  eventModal.value.show();
};

// Fermer la modal
const closeModal = () => {
  eventModal.value.hide();
};

// Navigation dans le calendrier
const prev = () => {
  calendar.value.prev();
};

const next = () => {
  calendar.value.next();
};

const today = () => {
  calendar.value.today();
};

// Changer la vue du calendrier
const changeView = (view) => {
  currentView.value = view;
  calendar.value.changeView(view);
};

// Formatage de la date courante
const formatCurrentDate = () => {
  if (!calendar.value) return '';
  
  const date = calendar.value.getDate();
  
  if (currentView.value === 'dayGridMonth') {
    return date.toLocaleString('fr-FR', { month: 'long', year: 'numeric' });
  } else if (currentView.value === 'timeGridWeek') {
    const start = calendar.value.view.activeStart;
    const end = calendar.value.view.activeEnd;
    
    const startStr = start.toLocaleString('fr-FR', { day: 'numeric', month: 'short' });
    const endStr = end.toLocaleString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    
    return `${startStr} - ${endStr}`;
  } else if (currentView.value === 'timeGridDay') {
    return date.toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }
  
  return '';
};

// Classes CSS pour les statuts
const getStatusClass = (status) => {
  const statusMap = {
    'scheduled': 'status-scheduled',
    'completed': 'status-completed',
    'cancelled': 'status-cancelled',
    'no_show': 'status-no-show'
  };
  
  return statusMap[status] || '';
};

// Labels pour les statuts
const getStatusLabel = (status) => {
  const statusMap = {
    'scheduled': 'Planifiée',
    'completed': 'Terminée',
    'cancelled': 'Annulée',
    'no_show': 'Absent'
  };
  
  return statusMap[status] || status;
};

// Formatage de la date
const formatDate = (date) => {
  return date.toLocaleString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Formatage de l'heure
const formatTime = (date) => {
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

// Cycle de vie du composant
onMounted(() => {
  initCalendar();
  eventModal.value = new Modal(document.getElementById('eventModal'));
});

onUnmounted(() => {
  if (calendar.value) {
    calendar.value.destroy();
  }
});
</script>

<style>
/* Styles FullCalendar */
.fc-toolbar-title {
  font-size: 1.25rem !important;
}

.fc-daygrid-day.fc-day-today,
.fc-timegrid-col.fc-day-today {
  background-color: rgba(33, 150, 243, 0.1) !important;
}

.fc-event {
  cursor: pointer;
  border-radius: 2px;
}

.fc-event:hover {
  filter: brightness(0.9);
}

/* Styles personnalisés pour le calendrier */
.calendar-container {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  padding: 1.5rem;
  height: calc(100vh - 160px);
  display: flex;
  flex-direction: column;
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

.calendar-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.calendar-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.current-date {
  font-size: 1.25rem;
  font-weight: 500;
  margin-bottom: 1rem;
  text-align: center;
}

.calendar {
  flex: 1;
  overflow: auto;
}

/* Styles pour la modal d'événement */
.event-status {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  margin-bottom: 1rem;
  font-weight: 500;
}

.status-scheduled {
  background-color: #e3f2fd;
  color: #0d47a1;
}

.status-completed {
  background-color: #e8f5e9;
  color: #1b5e20;
}

.status-cancelled {
  background-color: #ffebee;
  color: #b71c1c;
}

.status-no-show {
  background-color: #fff3e0;
  color: #e65100;
}

.event-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-row {
  display: flex;
}

.info-label {
  width: 100px;
  font-weight: 500;
}

.info-value {
  flex: 1;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .calendar-actions {
    flex-wrap: wrap;
  }
}
</style>