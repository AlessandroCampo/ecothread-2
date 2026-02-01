// Vuetify 3 config
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
// Icons
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

// (opzionale) locale
import { it } from 'vuetify/locale'

const lightTheme = {
  dark: false,
  colors: {
    // Background & Surface
    background: '#F9F9F9',
    surface: '#FFFFFF',
    
    // Primary - Verde Salvia (Sustainable Fashion)
    primary: '#748C70',
    'primary-darken-1': '#5A6D57',
    'primary-darken-2': '#404E3E',
    'primary-darken-3': '#343E32',
    'primary-darken-4': '#272F25',
    'primary-lighten-1': '#839980',
    'primary-lighten-2': '#93A690',
    'primary-lighten-3': '#A2B39F',
    'primary-lighten-4': '#B2BFAF',
    'primary-lighten-5': '#D1D9CF',
    
    // Secondary - Neutral Dark
    secondary: '#404040',
    'secondary-darken-1': '#202020',
    'secondary-lighten-1': '#606060',
    
    // State Colors
   error: '#B85C4D',
'error-darken-1': '#964839',
'error-lighten-1': '#CB7668',
    
success: '#A8943D',
'success-darken-1': '#8A7A32',
'success-lighten-1': '#F7F4E8',
    warning: '#E09126',
    'warning-lighten-1': '#F7E4C9',
    
   info: '#5B839A',
'info-darken-1': '#4A6B7D',
'info-lighten-1': '#7899AD',
warning: '#C4973B',
'warning-darken-1': '#A67D2D',
'warning-lighten-1': '#D4AD5C',
    
    // Grays
    'on-background': '#202020',
    'on-surface': '#202020',
    'grey-lighten-5': '#F9F9F9',
    'grey-lighten-4': '#EDEDED',
    'grey-lighten-3': '#DFDFDF',
    'grey-lighten-2': '#CBCBCB',
    'grey-lighten-1': '#ADADAD',
    'grey': '#868686',
    'grey-darken-1': '#606060',
    'grey-darken-2': '#404040',
    'grey-darken-3': '#202020',
    'grey-darken-4': '#0C0C0C',
  },
}

const darkTheme = {
  dark: true,
  colors: {
    background: '#121212',
    surface: '#1E1E1E',
    primary: '#90CAF9',
    secondary: '#B0BEC5',
    error: '#EF5350',
    info: '#29B6F6',
    success: '#66BB6A',
    warning: '#FFA726',
  },
}

const vuetify = createVuetify({
   components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: lightTheme,
      dark: darkTheme,
    },
  },

  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },

  locale: {
    locale: 'it',
    fallback: 'en',
    messages: { it },
  },

  defaults: {
    VBtn: {
      variant: 'flat',
      rounded: 'md',
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
    },
    VCard: {
      rounded: 'lg',
      elevation: 2,
    },
  },
})

export default vuetify
