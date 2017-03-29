export function clone(obj) {
  return JSON.parse(JSON.stringify(obj)); // deep clone http://heyjavascript.com/4-creative-ways-to-clone-objects/
}

export function maxIndexOf(collection, defaultValue = -1) {
  let lastKey = Math.max(...Object.keys(collection));
  return isFinite(lastKey) ? lastKey : defaultValue;
}

export function bindAllTo(callbacks, context, ...args) {
  const boundCallbacks = {};
  for (let callback in callbacks) {
    boundCallbacks[callback] = callbacks[callback].bind(this, ...args);
  }
  return boundCallbacks;
}

export function makeFullName(name, prefix) {
  if (prefix === void 0) {
    prefix = this.props.prefix;
  }
  return prefix ? prefix + '[' + name + ']' : name;
}

export function extractShortName(fullName) {
  const regexp = new RegExp('\\[([^\\[\\]]*)\\][^\\[\\]]*$', 'g');
  const matches = regexp.exec(fullName);
  return matches ? matches[1] : fullName;
}
