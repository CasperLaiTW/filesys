import parse from 'url-parse'

let settings = document.getElementById('settings')

const getUrl = function (url, path) {
  const uri = parse(url)
  uri.set('pathname', `${uri.pathname}/${path}`)
  return uri.href
}

/**
 * Get setting configuration from app headers.
 * @param {string} key Settings attribute key.
 * @param {boolean} [asJson] Parse value as json object.
 * @returns {string}
 */
const getSettings = function (key, asJson = false) {
  let strValue = settings.getAttribute(`data-${key}`)
  if (asJson) {
    return JSON.parse(strValue.replaceAll('\'', '"'))
  }

  return strValue
}

const mediaTypes = {
  file: 'file',
  dir: 'dir',
  image: 'image',
  media: 'media',
  document: 'document'
}

export default {
  getUrl,
  authorization: getSettings('authorization', true),
  dirIcon: getSettings('dir-icon-url'),
  filesUrl: getSettings('files-url'),
  foldersUrl: getSettings('folders-url'),
  iconDir: getSettings('icon-dir'),
  params: getSettings('params', true),
  treeUrl: getSettings('tree-url'),

  /**
   * Gets icon absolute URL depending on name.
   * @param {string} name
   * @returns {string|null}
   */
  icon (name) {
    return name ? `${this.iconDir}${name}.png` : null
  },

  /**
   * Gets current configuration target editor.
   * @returns {string}
   */
  target () {
    if (this.params && this.params.target) {
      return this.params.target.toLowerCase()
    }

    return 'input'
  },

  /**
   * Available media types.
   * @var {object}
   */
  mediaTypes,

  /**
   * Get currently available media type.
   * @returns {string}
   */
  mediaType () {
    if (this.params && this.params.type && mediaTypes[this.params.type]) {
      return this.params.type
    }

    return mediaTypes.file
  },

  /**
   * User defined callback name for file select.
   * @returns {boolean}
   */
  callback () {
    if (this.params && this.params.callback) {
      return this.params.callback
    }

    return false
  },

  /**
   * Get scoped image size or false if it is not set.
   * @return {Boolean|String}
   */
  imageSize () {
    if (this.mediaType() === mediaTypes.image && this.params && this.params.select) {
      return this.params.select
    }

    return false
  }
}
