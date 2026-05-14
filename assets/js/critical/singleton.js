class Singleton {
  static #instances = new WeakMap();

  constructor() {
    if (Singleton.#instances.has(this)) {
      throw new Error('Singleton instance should be retrieved via getInstance method, not via constructor.');
    }
  }

  static getInstance(...args) {
    if (!Singleton.#instances.has(this)) {
      Singleton.#instances.set(this, new this(...args));
    }

    return Singleton.#instances.get(this);
  }
}
