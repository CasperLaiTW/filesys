import Blob from '../../models/Blob'
import folderApi from '../../api/folder'
import settings from '../../settings'
import {
  blobs, selectedBlob, displayType, isGridView, isListView, pathUp,
  path
} from '../getters'
import { fetchContent, refreshContent } from '../actions'
import {
  removeSelectedBlob, removeBlob, setBlobs, setNewBlob, setSelectedBlob,
  setGridView, setListView, setLoadingStarted, setLoadingCompleted
} from '../mutations'

const state = {
  blobs: [],
  selected: false,
  displayType: 'grid' // 'grid'|'list'
}

const actions = {
  /**
   * Fetch content from server.
   * @param commit
   * @param getters
   */
  [fetchContent]: ({commit, getters}) => {
    commit(removeSelectedBlob)
    commit(setLoadingStarted)
    folderApi.content(getters[path])
      .then(blobs => {
        commit(setBlobs, {path: getters[path], pathUp: getters[pathUp], blobs})
        commit(setLoadingCompleted)
      })
  },

  /**
   * Refresh content from server.
   * @param dispatch
   */
  [refreshContent]: ({dispatch}) => {
    dispatch(fetchContent)
  }
}

const mutations = {
  /**
   * Deselect blob.
   * @param state
   */
  [removeSelectedBlob]: (state) => {
    deselectItems(state)
  },

  /**
   * Remove blob from state.
   * @param state
   * @param id
   */
  [removeBlob]: (state, id) => {
    let toRemove = state.blobs.filter(b => b.$id === id)[0]
    state.items.splice(state.blobs.indexOf(toRemove), 1)
  },

  /**
   * Mutate blobs of current state.
   * @param state
   * @param {String} path
   * @param {Array.<Blob>} blobs
   */
  [setBlobs]: (state, {path, pathUp, blobs}, rootState) => {
    state.blobs = blobs
    if (path !== '') {
      state.blobs.push(new Blob({
        name: '..',
        type: 'dir',
        full_name: pathUp,
        thumb: settings.dirIcon,
        $isSystem: true
      }))
    }
  },

  /**
   * Add new blob to current state.
   * @param state
   * @param {Blob} blob
   */
  [setNewBlob]: (state, blob) => {
    state.blobs.push(blob)
  },

  /**
   * Select blob for currents state.
   * @param state
   * @param {Blob} blob
   */
  [setSelectedBlob]: (state, blob) => {
    if (blob.$id !== state.selected.$id) {
      deselectItems(state)

      state.selected = blob
    }
  },

  /**
   * Set grid view for current state.
   * @param state
   */
  [setGridView]: (state) => {
    state.displayType = 'grid'
  },

  /**
   * Set list view for current state.
   * @param state
   */
  [setListView]: (state) => {
    state.displayType = 'list'
  }
}

function deselectItems (state) {
  let forRemove = -1
  state.blobs.forEach((item, index) => {
    item.$edit = false
    if (item.$temp) {
      forRemove = index
    }
  })

  if (~forRemove) {
    state.items.splice(forRemove, 1)
  }

  state.creating = false
  state.selected = false
}

const getters = {
  [blobs]: (store) => store.blobs,
  [isGridView]: (store) => store.displayType === 'grid',
  [isListView]: (store) => store.displayType === 'list',
  [selectedBlob]: (store) => store.selected,
  [displayType]: (store) => store.displayType
}

export default {state, actions, mutations, getters}