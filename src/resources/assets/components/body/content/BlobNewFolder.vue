<template>
  <transition name="fade-x-right">
    <div class="blob-container fade-x-right" v-if="isNewFolderEnabled">
      <blob :blob="blob"></blob>
    </div>
  </transition>
</template>

<script>
  import * as getters from '../../../store/getters'
  import blob from './Blob.vue'

  export default {
    name: 'new-folder-blob',

    computed: {
      /**
       * Determines is the current state enables new folder creation.
       * @return {Boolean}
       */
      isNewFolderEnabled () {
        return this.$store.getters[getters.getCreateFolderBlobVisibility]
      },

      /**
       * Get blob instance for new folder.
       * @return {Blob}
       */
      blob () {
        return this.$store.getters[getters.getNewFolder]
      }
    },

    components: {blob}
  }
</script>
