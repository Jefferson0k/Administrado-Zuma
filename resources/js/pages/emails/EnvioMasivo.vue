<template>
  <div class="min-h-screen bg-gray-100 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">📧 Envío Masivo de Emails</h1>
        <p class="text-gray-600">Envía emails personalizados a múltiples destinatarios</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario Principal -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-lg p-6">
            <form @submit.prevent="enviarEmails" class="space-y-6">
              <!-- Asunto del Email -->
              <div>
                <label for="asunto" class="block text-sm font-semibold text-gray-700 mb-2">
                  📝 Asunto del Email
                </label>
                <input
                  type="text"
                  id="asunto"
                  v-model="form.asunto"
                  placeholder="Ej: Newsletter semanal, Promociones especiales..."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                  required
                />
              </div>

              <!-- Lista de Emails -->
              <div>
                <label for="emails" class="block text-sm font-semibold text-gray-700 mb-2">
                  👥 Lista de Destinatarios
                </label>
                <textarea
                  id="emails"
                  v-model="form.emails"
                  rows="8"
                  placeholder="Ingresa los emails separados por comas, punto y coma o saltos de línea:&#10;&#10;juan@ejemplo.com, maria@ejemplo.com&#10;pedro@ejemplo.com; ana@ejemplo.com&#10;luis@ejemplo.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 font-mono text-sm"
                  required
                ></textarea>
                <div class="flex justify-between items-center mt-2">
                  <p class="text-sm text-gray-500">
                    <span class="font-medium text-blue-600">{{ validEmailsCount }}</span> emails válidos detectados
                  </p>
                  <button
                    type="button"
                    @click="limpiarEmails"
                    class="text-sm text-red-600 hover:text-red-800 transition duration-200"
                  >
                    Limpiar lista
                  </button>
                </div>
              </div>

              <!-- Mensaje -->
              <div>
                <label for="mensaje" class="block text-sm font-semibold text-gray-700 mb-2">
                  💬 Mensaje
                </label>
                <textarea
                  id="mensaje"
                  v-model="form.mensaje"
                  rows="12"
                  placeholder="Escribe tu mensaje aquí...&#10;&#10;Puedes usar saltos de línea y el formato se respetará en el email."
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                  required
                ></textarea>
                <p class="text-sm text-gray-500 mt-1">
                  {{ form.mensaje.length }} caracteres
                </p>
              </div>

              <!-- Botón de Envío -->
              <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                  type="button"
                  @click="previsualizarEmail"
                  class="px-6 py-3 bg-gray-600 text-white font-medium rounded-lg shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition duration-200"
                >
                  👁️ Previsualizar
                </button>
                <button
                  type="submit"
                  :disabled="loading || !isFormValid"
                  class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-medium rounded-lg shadow-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105 transition duration-200"
                >
                  <span v-if="loading" class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enviando...
                  </span>
                  <span v-else>🚀 Enviar Emails</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Panel de Información -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Resumen</h3>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600">Emails válidos:</span>
                <span class="font-semibold text-blue-600">{{ validEmailsCount }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Caracteres mensaje:</span>
                <span class="font-semibold">{{ form.mensaje.length }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Estado formulario:</span>
                <span :class="isFormValid ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                  {{ isFormValid ? '✅ Listo' : '❌ Incompleto' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Tips -->
          <div class="bg-blue-50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">💡 Consejos</h3>
            <ul class="text-sm text-blue-700 space-y-2">
              <li>• Separa emails con comas, punto y coma o saltos de línea</li>
              <li>• Los emails duplicados se eliminarán automáticamente</li>
              <li>• El sistema valida cada email antes del envío</li>
              <li>• Puedes previsualizar antes de enviar</li>
              <li>• Los saltos de línea se respetan en el mensaje</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Alertas -->
      <div v-if="alert.show" :class="alertClasses" class="mt-8 p-6 rounded-xl shadow-lg">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg v-if="alert.type === 'success'" class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <svg v-else class="h-6 w-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-lg font-medium" :class="alert.type === 'success' ? 'text-green-800' : 'text-red-800'">
              {{ alert.title }}
            </h3>
            <div class="mt-2 text-sm" :class="alert.type === 'success' ? 'text-green-700' : 'text-red-700'">
              <p>{{ alert.message }}</p>
              <div v-if="alert.errors && alert.errors.length > 0" class="mt-3">
                <p class="font-medium">Detalles de errores:</p>
                <ul class="mt-1 list-disc list-inside space-y-1">
                  <li v-for="error in alert.errors" :key="error">{{ error }}</li>
                </ul>
              </div>
            </div>
            <button
              @click="hideAlert"
              class="mt-3 text-sm underline hover:no-underline"
              :class="alert.type === 'success' ? 'text-green-600' : 'text-red-600'"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de Previsualización -->
      <div v-if="showPreview" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b">
            <div class="flex justify-between items-center">
              <h3 class="text-xl font-semibold">📧 Previsualización del Email</h3>
              <button @click="showPreview = false" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
          <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 140px);">
            <div class="border rounded-lg p-4 bg-gray-50">
              <p><strong>Para:</strong> [Destinatarios seleccionados]</p>
              <p><strong>Asunto:</strong> {{ form.asunto }}</p>
              <p><strong>De:</strong> {{ fromEmail }}</p>
            </div>
            <div class="mt-4 border rounded-lg p-4 bg-white">
              <div v-html="previewHtml"></div>
            </div>
          </div>
          <div class="p-6 border-t bg-gray-50">
            <button
              @click="showPreview = false"
              class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-200"
            >
              Cerrar Previsualización
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EnvioMasivo',
  data() {
    return {
      form: {
        emails: '',
        mensaje: '',
        asunto: ''
      },
      loading: false,
      showPreview: false,
      fromEmail: 'admin@zuma.com.pe',
      alert: {
        show: false,
        type: '',
        title: '',
        message: '',
        errors: []
      }
    }
  },
  computed: {
    validEmailsCount() {
      if (!this.form.emails.trim()) return 0
      return this.getValidEmails().length
    },
    
    isFormValid() {
      return this.form.asunto.trim() !== '' && 
             this.form.mensaje.trim() !== '' && 
             this.validEmailsCount > 0
    },
    
    alertClasses() {
      return this.alert.type === 'success' 
        ? 'bg-green-50 border border-green-200' 
        : 'bg-red-50 border border-red-200'
    },
    
    previewHtml() {
      return `
        <table style="background-color: #f0f1f9;max-width: 780px; width: 100%;padding-top: 2%;padding-bottom: 2%;padding-left: 4%;padding-right: 4%; font-family: Arial, sans-serif;">
          <tr>
            <td>
              <table style="width: 100%;border-collapse: collapse;">
                <thead>
                  <tr>
                    <td style="width: 20%;"></td>
                    <td style="text-align: center; width: 60%;">
                      <img src="https://fondeo.apros.global/email/autenthication/log-zuma.png" alt="ZUMA Logo" width="30%">
                    </td>
                    <td style="width: 20%;"></td>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><img src="https://fondeo.apros.global/email/autenthication/dola-2.png" alt="" width="70%"></td>
                    <td style="text-align: center;">
                      <p style="font-size: 20px; font-weight: 700; color: black;">Hola,</p>
                      <p style="font-size: 15px; color: black;">¡Te invitamos a completar tu información en ZUMA!</p>
                    </td>
                    <td></td>
                  </tr>
                  <tr>
                    <td></td>
                    <td style="padding-bottom: 40px;">
                      <p style="font-size: 15px; text-align: center; color: black; margin-bottom: 24px;">
                        ${this.form.mensaje.replace(/\n/g, '<br>')}
                      </p>
                      <div style="text-align: center;">
                        <a href="https://zuma.com.pe/inversionistas/editar/" target="_blank"
                          style="font-size: 14px; background-color: #4F91FF; color: white; padding: 12px 24px; border-radius: 200px; text-decoration: none; display: inline-block;">
                          Completar información
                        </a>
                      </div>
                    </td>
                    <td><img src="https://fondeo.apros.global/email/autenthication/simbolo-dolar-varios.png" alt="" width="70%"></td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr style="background-color: black;"><td colspan="3"></td></tr>
                  <tr>
                    <td></td>
                    <td style="font-size: 13px; text-align: center; padding-top: 24px;">
                      Si recibiste este mensaje por error, puedes ignorarlo.
                    </td>
                    <td></td>
                  </tr>
                  <tr>
                    <td></td>
                    <td style="font-size: 16px; text-align: center; padding-top: 12px; color: black;">
                      ¡Nos alegra tenerte con nosotros!<br>El equipo de ZUMA
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </td>
          </tr>
        </table>
      `
    }
  },
  methods: {
    getValidEmails() {
      if (!this.form.emails.trim()) return []
      const emails = this.form.emails.split(/[,;\s\n\r]+/).filter(email => {
        const trimmed = email.trim()
        return trimmed && this.isValidEmail(trimmed)
      })
      return [...new Set(emails)] // Eliminar duplicados
    },
    
    isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return re.test(email)
    },
    
    limpiarEmails() {
      this.form.emails = ''
    },
    
    previsualizarEmail() {
      if (!this.isFormValid) {
        this.showAlert('error', 'Formulario Incompleto', 'Por favor completa todos los campos antes de previsualizar.')
        return
      }
      this.showPreview = true
    },
    
    async enviarEmails() {
      this.loading = true
      this.hideAlert()
      
      try {
        const response = await axios.post('/property/enviar-emails', {
          emails: this.form.emails,
          mensaje: this.form.mensaje,
          asunto: this.form.asunto
        })
        
        if (response.data.success) {
          const { enviados, total, errores } = response.data
          let message = `${enviados} de ${total} emails enviados exitosamente.`
          
          this.showAlert('success', '🎉 Envío Completado', message, errores)
          this.resetForm()
        } else {
          this.showAlert('error', 'Error', response.data.message)
        }
      } catch (error) {
        let message = 'Error al enviar los emails'
        let errors = []
        
        if (error.response?.data?.message) {
          message = error.response.data.message
        }
        
        if (error.response?.data?.errors) {
          errors = Object.values(error.response.data.errors).flat()
        }
        
        this.showAlert('error', 'Error', message, errors)
      } finally {
        this.loading = false
      }
    },
    
    showAlert(type, title, message, errors = []) {
      this.alert = {
        show: true,
        type,
        title,
        message,
        errors
      }
      
      // Auto ocultar después de 15 segundos si es éxito
      if (type === 'success') {
        setTimeout(() => {
          this.hideAlert()
        }, 15000)
      }
    },
    
    hideAlert() {
      this.alert.show = false
    },
    
    resetForm() {
      this.form = {
        emails: '',
        mensaje: '',
        asunto: ''
      }
    }
  }
}
</script>

<style scoped>
/* Animaciones personalizadas */
.transform {
  transition: transform 0.2s;
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

/* Scrollbar personalizado para el modal */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>