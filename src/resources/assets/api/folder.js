import vue from 'vue'
import Blob from '../models/Blob'
import settings from '../settings'

export default {
  /**
   * Receive folder content.
   * @param {string} path
   * @returns {Promise.<Array.<Blob>>}
   */
  content (path) {
    return new Promise((resolve, reject) => {
      vue.http.get(settings.getUrl(settings.foldersUrl, path))
        .then(({data}) => {
          let blobs = data.map(blob => new Blob(blob))
          resolve(blobs)
        }, reject)
    })
  },

  /**
   * Create new folder.
   * @param {Blob} blob
   * @param {string} name
   * @returns {Promise.<Object>}
   */
  create (blob, name) {
    return new Promise((resolve, reject) => {
      vue.http.post(settings.foldersUrl, {folder: blob.dir, name})
        .then(({data}) => { resolve(new Blob(data)) }, reject)
    })
  },

  /**
   * Rename folder.
   * @param {Blob} blob
   * @param {string} name
   * @returns {Promise.<Blob>}
   */
  update (blob, name) {
    return new Promise((resolve, reject) => {
      vue.http.patch(settings.getUrl(settings.foldersUrl, blob.path), {name})
        .then(({data}) => { resolve(new Blob(data)) }, reject)
    })
  },

  /**
   * Delete folder.
   * @param {Blob} blob
   * @returns {Promise.<Boolean>}
   */
  delete (blob) {
    return new Promise((resolve, reject) => {
      vue.http.delete(settings.getUrl(settings.foldersUrl, blob.path))
        .then(({data}) => { resolve(!!data) }, reject)
    })
  }
}
