import { useState } from 'react';

type Lang = 'es' | 'en' | 'fr';
type Zone =
  | 'ciudad' | 'boquete' | 'coronado' | 'bocas'
  | 'david' | 'elvalle' | 'pedasi'
  | 'volcan' | 'santafe' | 'santacatalina' | 'mariato' | 'playavenao' | 'sanblas';
type Level = 'frugal' | 'comodo' | 'premium';

interface Categories {
  alquiler: number;
  comida: number;
  transporte: number;
  utilidades: number;
  salud: number;
  ocio: number;
}

// Presets basados en datos de mercado 2025-2026 verificados con múltiples fuentes
// (International Living, Numbeo, Expat Exchange, iGo Panama, Panama Life Hub)
const presets: Record<Zone, Record<Level, Categories>> = {
  ciudad: {
    // Barrios exteriores o edificios más antiguos; metro + bus público
    frugal:  { alquiler: 700,  comida: 350, transporte: 100, utilidades: 130, salud: 80,  ocio: 150 },
    // San Francisco / El Cangrejo; carro propio opcional
    comodo:  { alquiler: 1200, comida: 550, transporte: 180, utilidades: 180, salud: 150, ocio: 250 },
    // Punta Pacífica / Costa del Este / Sky Tower; carro + amenidades premium
    premium: { alquiler: 2500, comida: 900, transporte: 350, utilidades: 280, salud: 250, ocio: 500 },
  },
  boquete: {
    // Estudio o habitación compartida; sin carro; mercado local; 1 persona
    frugal:  { alquiler: 650,  comida: 280, transporte: 50,  utilidades: 90,  salud: 100, ocio: 100 },
    // Casa 2-hab amoblada ~$750-900; electricidad ~$25 (sin A/C); seguro 2 personas; carro
    comodo:  { alquiler: 900,  comida: 400, transporte: 120, utilidades: 95,  salud: 175, ocio: 180 },
    // Villa con vista al Barú; zona expat premium; carro + viajes; seguro top
    premium: { alquiler: 1500, comida: 650, transporte: 200, utilidades: 130, salud: 250, ocio: 320 },
  },
  coronado: {
    // Condominios básicos tierra adentro; sin carro propio
    frugal:  { alquiler: 450,  comida: 280, transporte: 120, utilidades: 100, salud: 70,  ocio: 100 },
    // Apto frente al mar o casa en comunidad cerrada; carro
    comodo:  { alquiler: 900,  comida: 450, transporte: 180, utilidades: 150, salud: 130, ocio: 200 },
    // Villa/penthouse frente al mar; múltiples amenidades
    premium: { alquiler: 1600, comida: 680, transporte: 280, utilidades: 200, salud: 200, ocio: 350 },
  },
  bocas: {
    // Isla: todo llega en bote; cuarto o estudio básico
    frugal:  { alquiler: 300,  comida: 260, transporte: 80,  utilidades: 90,  salud: 60,  ocio: 100 },
    // Casa/apto en Bocas Town; lancha propia; servicios básicos
    comodo:  { alquiler: 600,  comida: 400, transporte: 130, utilidades: 130, salud: 100, ocio: 180 },
    // Eco-villa frente al mar; kayaks/botes; viajes frecuentes
    premium: { alquiler: 1050, comida: 600, transporte: 220, utilidades: 170, salud: 150, ocio: 280 },
  },
  david: {
    // Ciudad más grande del interior; barrio local; transporte público
    frugal:  { alquiler: 300,  comida: 250, transporte: 70,  utilidades: 80,  salud: 60,  ocio: 80  },
    // Apto moderno; carro propio; restaurantes locales
    comodo:  { alquiler: 600,  comida: 380, transporte: 120, utilidades: 120, salud: 120, ocio: 150 },
    // Casa en comunidad cerrada; zona comercial; seguro privado
    premium: { alquiler: 1000, comida: 550, transporte: 200, utilidades: 160, salud: 180, ocio: 250 },
  },
  elvalle: {
    // Cráter volcánico; clima fresco todo el año; mercado campesino
    frugal:  { alquiler: 350,  comida: 250, transporte: 60,  utilidades: 100, salud: 60,  ocio: 80  },
    // Casita o apto con jardín; carro o transfer a la ciudad
    comodo:  { alquiler: 600,  comida: 380, transporte: 100, utilidades: 130, salud: 120, ocio: 160 },
    // Casa de campo tipo finca; paisaje natural; excursiones
    premium: { alquiler: 1000, comida: 580, transporte: 180, utilidades: 170, salud: 180, ocio: 260 },
  },
  pedasi: {
    // Pueblo costero; vida sencilla; acceso al mar
    frugal:  { alquiler: 300,  comida: 220, transporte: 80,  utilidades: 80,  salud: 60,  ocio: 70  },
    // Casita cercana al pueblo; carro; playa y surf
    comodo:  { alquiler: 600,  comida: 350, transporte: 120, utilidades: 120, salud: 100, ocio: 130 },
    // Casa frente al mar o finca grande; temporada alta
    premium: { alquiler: 950,  comida: 520, transporte: 200, utilidades: 160, salud: 150, ocio: 220 },
  },
  // --- Zonas adicionales verificadas 2025-2026 ---
  volcan: {
    // Chiriquí highlands ~1200m; sin A/C; electricidad ~$30-50/mes; más barato que Boquete 30%
    frugal:  { alquiler: 350,  comida: 250, transporte: 60,  utilidades: 70,  salud: 60,  ocio: 80  },
    // Apt/casa amueblada $500-900; restaurantes locales; carro propio
    comodo:  { alquiler: 650,  comida: 380, transporte: 110, utilidades: 100, salud: 120, ocio: 150 },
    // Casa con jardín y vista al Barú; zona expat creciente
    premium: { alquiler: 1100, comida: 580, transporte: 190, utilidades: 150, salud: 180, ocio: 260 },
  },
  santafe: {
    // Veraguas highlands; una de las zonas más baratas de Panamá; casas $475/mes amuebladas
    frugal:  { alquiler: 300,  comida: 200, transporte: 50,  utilidades: 70,  salud: 50,  ocio: 60  },
    // Casita con jardín; mercado campesino; carro necesario para ciudad
    comodo:  { alquiler: 500,  comida: 320, transporte: 90,  utilidades: 100, salud: 100, ocio: 120 },
    // Finca o casa de campo amplia; naturaleza pura; viajes esporádicos a Santiago
    premium: { alquiler: 850,  comida: 500, transporte: 160, utilidades: 140, salud: 160, ocio: 220 },
  },
  santacatalina: {
    // Pueblo surf en Veraguas; ~$1,200 frugal confirmado por Expat Exchange; sin A/C en noches
    frugal:  { alquiler: 400,  comida: 230, transporte: 80,  utilidades: 80,  salud: 60,  ocio: 80  },
    // Casa/apto con brisa marina; tablas y equipo surf; restaurantes locales
    comodo:  { alquiler: 700,  comida: 350, transporte: 130, utilidades: 120, salud: 100, ocio: 130 },
    // Villa frente a la ola; lancha de pesca; viajes a ciudad
    premium: { alquiler: 1200, comida: 550, transporte: 200, utilidades: 200, salud: 150, ocio: 220 },
  },
  mariato: {
    // Torio / Mariato / Morrillo – Sunset Coast emergente; surf; naturaleza prístina
    frugal:  { alquiler: 350,  comida: 220, transporte: 80,  utilidades: 80,  salud: 60,  ocio: 80  },
    // Casita o container-cabin en la costa; carro 4x4 necesario
    comodo:  { alquiler: 650,  comida: 360, transporte: 130, utilidades: 120, salud: 100, ocio: 140 },
    // Villa con vista al Pacífico; botes; comunidad expat pequeña
    premium: { alquiler: 1100, comida: 540, transporte: 200, utilidades: 170, salud: 150, ocio: 230 },
  },
  playavenao: {
    // Azuero; comunidad surf y kite; parejas cómodas $1,500-2,000/mes (DoPanama 2024)
    frugal:  { alquiler: 400,  comida: 230, transporte: 80,  utilidades: 100, salud: 60,  ocio: 90  },
    // Alquiler $700-1,200 cerca del agua; A/C necesario; pareja ~$500/mes comida
    comodo:  { alquiler: 750,  comida: 380, transporte: 130, utilidades: 150, salud: 110, ocio: 160 },
    // Casa de playa con piscina; kite club; viajes a Pedasí o Chitré
    premium: { alquiler: 1300, comida: 580, transporte: 220, utilidades: 200, salud: 170, ocio: 280 },
  },
  sanblas: {
    // ATENCIÓN: Comarca autónoma Guna Yala. Extranjeros NO pueden residir de forma permanente.
    // Precios para estadías extendidas (nómadas, veleros, acuerdo con familias locales).
    // Sin ATMs, sin electricidad 24h, sin internet estable, sin servicios médicos en isla.
    // Costo importado: todo llega en bote desde tierra firme.
    frugal:  { alquiler: 800,  comida: 400, transporte: 150, utilidades: 80,  salud: 100, ocio: 100 },
    // Cabaña privada con familia Guna; bote propio; generador nocturno
    comodo:  { alquiler: 1400, comida: 600, transporte: 200, utilidades: 120, salud: 150, ocio: 200 },
    // Velero propio o cabaña premium; lancha rápida; seguro de evacuación médica
    premium: { alquiler: 2200, comida: 800, transporte: 300, utilidades: 180, salud: 300, ocio: 350 },
  },
};

interface ZoneDef {
  id: Zone;
  emoji: string;
  label: Record<Lang, string>;
  tag?: Record<Lang, string>;
  // Si warning está presente, se muestra un aviso especial en el total
  warning?: Record<Lang, string>;
}

const zones: ZoneDef[] = [
  // — Principales destinos expat —
  { id: 'ciudad',        emoji: '🏙️', label: { es: 'Ciudad de Panamá',   en: 'Panama City',       fr: 'Panama City'        } },
  { id: 'boquete',       emoji: '⛰️', label: { es: 'Boquete',             en: 'Boquete',           fr: 'Boquete'            } },
  { id: 'coronado',      emoji: '🌅', label: { es: 'Coronado',            en: 'Coronado',          fr: 'Coronado'           } },
  { id: 'bocas',         emoji: '🌊', label: { es: 'Bocas del Toro',      en: 'Bocas del Toro',    fr: 'Bocas del Toro'     } },
  // — Interior y montaña —
  { id: 'david',         emoji: '🌄', label: { es: 'David, Chiriquí',     en: 'David, Chiriquí',   fr: 'David, Chiriquí'    }, tag: { es: 'Más económico', en: 'Most affordable', fr: 'Le plus abordable' } },
  { id: 'elvalle',       emoji: '🌿', label: { es: 'El Valle de Antón',   en: 'El Valle de Antón', fr: 'El Valle de Antón'  }, tag: { es: 'Cráter volcánico', en: 'Volcanic crater', fr: 'Cratère volcanique' } },
  { id: 'volcan',        emoji: '🏔️', label: { es: 'Volcán, Chiriquí',    en: 'Volcán, Chiriquí',  fr: 'Volcán, Chiriquí'   }, tag: { es: 'Sin A/C necesario', en: 'No A/C needed', fr: 'Sans climatisation' } },
  { id: 'santafe',       emoji: '🌱', label: { es: 'Santa Fe, Veraguas',  en: 'Santa Fe, Veraguas',fr: 'Santa Fe, Veraguas'  }, tag: { es: 'La más tranquila', en: 'Off the beaten path', fr: 'La plus tranquille' } },
  // — Costa Pacífico —
  { id: 'pedasi',        emoji: '🏄', label: { es: 'Pedasí',              en: 'Pedasí',            fr: 'Pedasí'             }, tag: { es: 'Azuero', en: 'Azuero', fr: 'Azuero' } },
  { id: 'playavenao',    emoji: '🤙', label: { es: 'Playa Venao',         en: 'Playa Venao',       fr: 'Playa Venao'        }, tag: { es: 'Surf & kite', en: 'Surf & kite', fr: 'Surf & kite' } },
  { id: 'santacatalina', emoji: '🏊', label: { es: 'Santa Catalina',      en: 'Santa Catalina',    fr: 'Santa Catalina'     }, tag: { es: 'Surf world-class', en: 'World-class surf', fr: 'Surf de classe mondiale' } },
  { id: 'mariato',       emoji: '🌇', label: { es: 'Torio / Mariato',     en: 'Torio / Mariato',   fr: 'Torio / Mariato'    }, tag: { es: 'Sunset Coast', en: 'Sunset Coast', fr: 'Côte du Coucher' } },
  // — Especial —
  {
    id: 'sanblas', emoji: '🏝️', label: { es: 'San Blas (Guna Yala)', en: 'San Blas (Guna Yala)', fr: 'San Blas (Guna Yala)' },
    tag: { es: '⚠️ Solo estadías', en: '⚠️ Extended stays only', fr: '⚠️ Séjours prolongés' },
    warning: {
      es: '⚠️ Territorio Autónomo Guna Yala: los extranjeros NO pueden arrendar ni residir de forma permanente. Precios estimados para estadías extendidas (nómadas digitales, veleros). Sin ATMs, electricidad limitada ni servicios médicos en isla.',
      en: '⚠️ Guna Yala Autonomous Territory: foreigners CANNOT rent or reside permanently. Estimates for extended stays (digital nomads, sailboats). No ATMs, limited electricity, no medical services on islands.',
      fr: '⚠️ Territoire Autonome Guna Yala : les étrangers NE PEUVENT PAS louer ni résider de façon permanente. Prix estimés pour séjours prolongés (nomades digitaux, voiliers). Sans distributeurs, électricité limitée ni services médicaux.',
    },
  },
];

const levels: { id: Level; label: Record<Lang, string>; desc: Record<Lang, string> }[] = [
  { id: 'frugal',  label: { es: 'Austero',   en: 'Frugal',       fr: 'Économique'  }, desc: { es: 'Cocinas en casa, transporte público, vida tranquila', en: 'Cook at home, public transport, quiet life', fr: 'Cuisiner à la maison, transports en commun, vie tranquille' } },
  { id: 'comodo',  label: { es: 'Cómodo',    en: 'Comfortable',  fr: 'Confortable' }, desc: { es: 'Comes fuera con regularidad, carro propio opcional', en: 'Eat out regularly, car optional',              fr: 'Manger au restaurant régulièrement, voiture optionnelle'   } },
  { id: 'premium', label: { es: 'Premium',   en: 'Premium',      fr: 'Premium'     }, desc: { es: 'Restaurantes, viajes, amenidades de alta gama',      en: 'Restaurants, travel, premium amenities',       fr: 'Restaurants, voyages, commodités haut de gamme'           } },
];

const categoryLabels: Record<keyof Categories, Record<Lang, string>> = {
  alquiler:   { es: 'Alquiler / vivienda',               en: 'Rent / housing',                        fr: 'Loyer / logement'                          },
  comida:     { es: 'Alimentación',                      en: 'Food',                                  fr: 'Alimentation'                              },
  transporte: { es: 'Transporte',                        en: 'Transport',                             fr: 'Transport'                                 },
  utilidades: { es: 'Servicios (luz, agua, internet)',   en: 'Utilities (electricity, water, internet)', fr: 'Services (électricité, eau, internet)'   },
  salud:      { es: 'Salud / seguro médico',             en: 'Health / insurance',                    fr: 'Santé / assurance maladie'                 },
  ocio:       { es: 'Ocio y salidas',                    en: 'Entertainment',                         fr: 'Loisirs et sorties'                        },
};

export default function CostCalculator({ lang = 'es' }: { lang?: Lang }) {
  const [zone, setZone] = useState<Zone>('ciudad');
  const [level, setLevel] = useState<Level>('comodo');
  const [values, setValues] = useState<Categories>(presets.ciudad.comodo);
  const activeZone = zones.find(z => z.id === zone);

  function applyPreset(z: Zone, l: Level) {
    setZone(z);
    setLevel(l);
    setValues({ ...presets[z][l] });
  }

  const total = Object.values(values).reduce((a, b) => a + b, 0);

  const t = {
    title:      { es: 'Calculadora de Costo Real',              en: 'Real Cost Calculator',                     fr: 'Calculateur de coût réel'                          },
    zone:       { es: 'Zona',                                   en: 'Area',                                     fr: 'Zone'                                              },
    lifestyle:  { es: 'Estilo de vida',                         en: 'Lifestyle',                                fr: 'Style de vie'                                      },
    breakdown:  { es: 'Desglose mensual',                       en: 'Monthly breakdown',                        fr: 'Ventilation mensuelle'                             },
    total:      { es: 'Total estimado / mes',                   en: 'Estimated total / month',                  fr: 'Total estimé / mois'                               },
    adjust:     { es: 'Ajusta los valores a tu caso',           en: 'Adjust the values to your situation',      fr: 'Ajustez les valeurs à votre situation'             },
    disclaimer: { es: 'Datos de referencia 2025–2026 basados en Numbeo, International Living, iGo Panama y Expat Exchange. Los precios varían según ubicación exacta, temporada y negociación.', en: 'Reference data 2025–2026 based on Numbeo, International Living, iGo Panama, and Expat Exchange. Prices vary by exact location, season, and negotiation.', fr: 'Données de référence 2025–2026 basées sur Numbeo, International Living, iGo Panama et Expat Exchange. Les prix varient selon l\'emplacement exact, la saison et la négociation.' },
  };

  return (
    <div className="space-y-8">

      {/* Zone selector */}
      <div>
        <p className="text-sm font-semibold text-slate uppercase tracking-wide mb-3">{t.zone[lang]}</p>
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2">
          {zones.map(z => (
            <button
              key={z.id}
              onClick={() => applyPreset(z.id, level)}
              className={`p-2.5 rounded-xl border text-xs font-medium transition-all text-left ${
                zone === z.id
                  ? 'border-jade bg-jade/10 text-jade'
                  : 'border-jade/15 bg-white text-slate-mid hover:border-jade/30'
              }`}
            >
              <span className="block text-lg mb-0.5">{z.emoji}</span>
              <span className="block leading-tight text-[11px] sm:text-xs">{z.label[lang]}</span>
              {z.tag && (
                <span className={`inline-block mt-1 text-[9px] px-1.5 py-0.5 rounded-full font-normal leading-tight ${
                  zone === z.id ? 'bg-jade/20 text-jade' : 'bg-slate-100 text-slate-mid'
                }`}>
                  {z.tag[lang]}
                </span>
              )}
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

      {/* Warning for special zones */}
      {activeZone?.warning && (
        <div className="p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-800 text-xs leading-relaxed">
          {activeZone.warning[lang]}
        </div>
      )}

      {/* Total */}
      <div className="p-6 rounded-2xl bg-jade text-white text-center">
        <p className="text-sm opacity-80 mb-1">{t.total[lang]}</p>
        <p className="font-serif text-4xl font-bold">${total.toLocaleString()}</p>
        <p className="text-xs opacity-60 mt-3">{t.disclaimer[lang]}</p>
      </div>

    </div>
  );
}
