// Live-Vorschau der Fristen im Vertragsformular. Die Berechnung macht der Server,
// damit Formular, Erinnerungen und Anzeige dieselbe Logik verwenden.
export function contractForm(initial = {}) {
  return {
    anchor: initial.anchor ?? 'term_end',
    preview: null,
    loading: false,
    timer: null,

    init() {
      this.$nextTick(() => this.refresh());
      this.$el.addEventListener('input', () => this.schedule());
      this.$el.addEventListener('change', () => this.schedule());
    },

    schedule() {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.refresh(), 350);
    },

    async refresh() {
      const form = this.$el.closest('form') ?? this.$el;
      const data = new FormData(form);
      data.delete('_method');
      this.loading = true;

      try {
        const res = await fetch(initial.previewUrl, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: data,
        });
        if (res.ok) this.preview = await res.json();
      } catch (e) {
        // Vorschau ist optional; Speichern funktioniert auch ohne.
      } finally {
        this.loading = false;
      }
    },
  };
}
