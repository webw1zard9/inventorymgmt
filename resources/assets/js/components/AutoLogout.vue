<script setup>

import { onMounted, onBeforeUnmount, ref } from "vue";
import VueBasicAlert from "vue-basic-alert";

const props = defineProps({
  warningTime: {
    type: Number,
    default: 60 * 30 * 1000
  },
  logoutTime: {
    type: Number,
    default: 60 * 32 * 1000
  },
  data: {
    type: Array,
    default: () => ['click', 'scroll', 'keypress']
  }
});

const alert = ref(null);
const warningTimer = ref(null);
const logoutTimer = ref(null);

const startTimer = () => {
  warningTimer.value = setTimeout(warningMessage, props.warningTime); // Assuming `time` is another prop you forgot to mention
  logoutTimer.value = setTimeout(logoutUser, props.logoutTime); // Assuming `time` is another prop you forgot to mention
}

const warningMessage = () => {
  alert.value.showAlert(
      'warning',
      'You will be logged out automatically in 1 minute',
      'Warning',
  );
}

const logoutUser = async () => {
  try {
    const response = await axios.post('/logout');

    if (response.status === 204) {
      // Handle successful logout, e.g., redirecting the user
      window.location.href = 'http://www.google.com';
    }
  } catch (error) {
    console.error("Error logging out:", error);
    // Handle any errors during logout here
  }
}

const resetTimer = () => {
  // console.log('reset timer');
  clearTimeout(warningTimer.value);
  clearTimeout(logoutTimer.value);
  alert.value.closeAlert();
  startTimer();
}

onMounted(() => {
  // console.log('mounted');
  props.data.forEach(event => {
    // console.log(event);
    window.addEventListener(event, resetTimer);
  });
  startTimer();
});

onBeforeUnmount(() => {
  props.data.forEach(event => {
    window.removeEventListener(event, resetTimer);
  });
  clearTimeout(warningTimer.value);
  clearTimeout(logoutTimer.value);
});

</script>


<template>

  <vue-basic-alert
      :duration="300"
      @close="resetTimer"
      ref="alert" />

</template>
