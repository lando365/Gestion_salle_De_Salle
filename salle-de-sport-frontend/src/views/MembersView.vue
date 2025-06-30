<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold text-gray-900">Gestion des Membres</h2>
    </div>

    <MemberList @create="showCreateModal = true" @edit="openEditModal" @delete="deleteMember" />

    <!-- Modals -->
    <div v-if="showCreateModal || currentMember" class="fixed inset-0 overflow-y-auto z-50">
      <div
        class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
      >
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"
          >&#8203;</span
        >

        <div
          class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
        >
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              {{ currentMember ? 'Modifier Membre' : 'Nouveau Membre' }}
            </h3>

            <MemberForm
              :member-id="currentMember?.id"
              :initial-data="currentMember || {}"
              @submit="handleSubmit"
              @cancel="closeModal"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useMemberStore } from '@/stores/members'
import MemberList from '@/components/MemberList.vue'
import MemberForm from '@/components/MemberForm.vue'

const memberStore = useMemberStore()

const showCreateModal = ref(false)
const currentMember = ref(null)

const openEditModal = (member) => {
  currentMember.value = { ...member }
}

const closeModal = () => {
  showCreateModal.value = false
  currentMember.value = null
}

const handleSubmit = async (formData) => {
  try {
    if (currentMember.value) {
      await memberStore.updateMember(currentMember.value.id, formData)
    } else {
      await memberStore.createMember(formData)
    }
    closeModal()
  } catch (error) {
    console.error('Error saving member:', error)
  }
}

const deleteMember = async (id) => {
  try {
    await memberStore.deleteMember(id)
  } catch (error) {
    console.error('Error deleting member:', error)
  }
}
</script>
