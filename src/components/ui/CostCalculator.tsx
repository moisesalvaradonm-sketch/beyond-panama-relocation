import { useState } from 'react';

type Lang = 'es' | 'en';
type Zone = 'ciudad' | 'boquete' | 'coronado' | 'bocas';
type Level = 'frugal' | 'comodo' | 'premium';

interface Categories {
  alquiler: number;
  comida: number;
  transporte: number;
  utilidades: number;
  salud: number;
  ocio: number;
}

const presets: Record<Zone, Record<Level, Categories>> = {
  ciudad: {
    frugal:  { alquiler: 600,  comida: 350, transporte: 100, utilidades: 120, salud: 80,  ocio: 150 },
    comodo:  { alquiler: 1200, comida: 550, transporte: 180, utilidades: 180, salud: 150, ocio: 250 },
    premium: { alquiler: 2500, comida: 900, transporte: 350, utilidades: 280, salud: 250, ocio: 500 },
  },
  boquete: {
    frugal:  { alquiler: 350, comida: 280, transporte: 80,  utilidades: 90,  salud: 70,  ocio: 100 },
    comodo:  { alquiler: 700, comida: 420, transporte: 130, utilidades: 130, salud: 130, ocio: 180 },
    premium: { alquiler: 1200, comida: 650, transporte: 220, utilidades: 180, salud: 200, ocio: 300 },
  },
  coronado: {
    frugal:  { alquiler: 400, comida: 280, transporte: 120, utilidades: 100, salud: 70,  ocio: 100 },
    comodo:  { alquiler: 800, comida: 450, transporte: 180, utilidades: 150, salud: 130, ocio: 200 },
    premium: { alquiler: 1500, comida: 680, transporte: 280, utilidades: 200, salud: 200, ocio: 350 },
  },
  bocas: {
    frugal:  { alquiler: 250, comida: 230, transporte: 80,  utilidades: 60,  salud: 60,  ocio: 80  },
    comodo:  { alquiler: 500, comida: 380, transporte: 130, utilidades: 100, salud: 100, ocio: 150 },
    premium: { alquiler: 900, comida: 580, transporte: 220, utilidades: 150, salud: 150, ocio: 250 },
  },
};

const zones: { id: Zone; emoji: string; label: Record<Lang, string> }[] = [
  { id: 'ciudad',   emoji: '🏙️', label: { es: 'Ciudad de Panamá', en: 'Panama City' } },
  { id: 'boquete',  emoji: '⛰️', label: { es: 'Boquete',          en: 'Boquete'     } },
  { id: 'coronado', emoji: '🌅', label: { es: 'Coronado',         en: 'Coronado'    } },
  { id: 'bocas',    emoji: '🌊', label: { es: 'Bocas del Toro',   en: 'Bocas del Toro' } },
];

const levels: { id: Level; label: Record<Lang, string>; desc: Record<Lang, string> }[] = [
  { id: 'frugal',  label: { es: 'Austero',   en: 'Frugal'      }, desc: { es: 'Cocinas en casa, transporte público, vida tranquila', en: 'Cook at home, public transport, quiet life' } },
  { id: 'comodo',  label: { es: 'Cómodo',    en: 'Comfortable' }, desc: { es: 'Comes fuera con regularidad, carro propio opcional', en: 'Eat out regularly, car optional'               } },
  { id: 'premium', label: { es: 'Premium',   en: 'Premium'     }, desc: { es: 'Restaurantes, viajes, amenidades de alta gama',      en: 'Restaurants, travel, premium amenities'        } },
];

const categoryLabels: Record<keyof Categories, Record<Lang, string>> = {
  alquiler:    { es: 'Alquiler / vivienda', en: 'Rent / housing'  },
  comida:      { es: 'Alimentación',        en: 'Food'            },
  transporte:  { es: 'Transporte',          en: 'Transport'       },
  utilidades:  { es: 'Servicios (luz, agua, internet)', en: 'Utilities (electricity, water, internet)' },
  salud:       { es: 'Salud / seguro médico', en: 'Health / insurance' },
  ocio:        { es: 'Ocio y salidas',      en: 'Entertainment'   },
};

export default function CostCalculator({ lang = 'es' }: { lang?: Lang }) {
  const [zone, setZone] = useState<Zone>('ciudad');
  const [level, setLevel] = useState<Level>('comodo');
  const [values, setValues] = useState<Categories>(presets.ciudad.comodo);

  function applyPreset(z: Zone, l: Level) {
    setZone(z);
    setLevel(l);
    setValues({ ...presets[z][l] });
  }

  const total = Object.values(values).reduce((a, b) => a + b, 0);

  const t = {
    title:      { es: 'Calculadora de Costo Real', en: 'Real Cost Calculator' },
    zone:       { es: 'Zona', en: 'Area' },
    lifestyle:  { es: 'Estilo de vida', en: 'Lifestyle' },
    breakdown:  { es: 'Desglose mensual', en: 'Monthly breakdown' },
    total:      { es: 'Total estimado / mes', en: 'Estimated total / month' },
    adjust:     { es: 'Ajusta los valores a tu caso', en: 'Adjust the values to your situation' },
    disclaimer: { es: 'Estimaciones de referencia 2025–2026. No representan garantía de precios actuales.', en: 'Reference estimates for 2025–2026. Do not represent current price guarantees.' },
  };

  return (
    <div className="space-y-8">

      {/* Zone selector */}
      <div>
        <p className="text-sm font-semibold text-slate uppercase tracking-wide mb-3">{t.zone[lang]}</p>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
          {zones.map(z => (
            <button
              key={z.id}
              onClick={() => applyPreset(z.id, level)}
              className={`p-3 rounded-xl border text-sm font-medium transition-all text-left ${
                zone === z.id
                  ? 'border-jade bg-jade/10 text-jade'
                  : 'border-jade/15 bg-white text-slate-mid hover:border-jade/30'
              }`}
            >
              <span className="block text-xl mb-1">{z.emoji}</span>
              {z.label[lang]}
            </button>
          ))}
        </div>
      </div>

      {/* Level selector */}
      <div>
        <p className="text-sm font-semibold text-slate uppercase tracking-wide mb-3">{t.lifestyle[lang]}</p>
        <div className="grid grid-cols-3 gap-2">
          {levels.map(l => (
            <button
              key={l.id}
              onClick={() => applyPreset(zone, l.id)}
              className={`p-3 rounded-xl border text-sm transition-all text-left ${
                level === l.id
                  ? 'border-jade bg-jade/10 text-jade'
                  : 'border-jade/15 bg-white text-slate-mid hover:border-jade/30'
              }`}
            >
              <span className="font-semibold block">{l.label[lang]}</span>
              <span className="text-xs opacity-80 mt-0.5 block">{l.desc[lang]}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Breakdown + sliders */}
      <div>
        <p className="text-sm font-semibold text-slate uppercase tracking-wide mb-3">{t.adjust[lang]}</p>
        <div className="space-y-4 bg-white rounded-2xl border border-jade/10 p-5">
          {(Object.keys(values) as Array<keyof Categories>).map(cat => (
            <div key={cat}>
              <div className="flex justify-between items-center mb-1">
                <label className="text-sm text-slate">{categoryLabels[cat][lang]}</label>
                <span className="text-sm font-semibold text-slate">${values[cat].toLocaleString()}</span>
              </div>
              <input
                type="range"
                min={0}
                max={cat === 'alquiler' ? 4000 : cat === 'comida' ? 1500 : 800}
                step={25}
                value={values[cat]}
                onChange={e => setValues(v => ({ ...v, [cat]: Number(e.target.value) }))}
                className="w-full accent-jade h-1.5 rounded-full"
              />
            </div>
          ))}
        </div>
      </div>

      {/* Total */}
      <div className="p-6 rounded-2xl bg-jade text-white text-center">
        <p className="text-sm opacity-80 mb-1">{t.total[lang]}</p>
        <p className="font-serif text-4xl font-bold">${total.toLocaleString()}</p>
        <p className="text-xs opacity-60 mt-3">{t.disclaimer[lang]}</p>
      </div>

    </div>
  );
}
