import { useState } from 'react';

type Lang = 'es' | 'en' | 'fr';
type StepType = 'radio' | 'multiselect' | 'textarea' | 'contact' | 'form' | 'tip';

interface Option {
  value: string;
  label: string;
  sublabel?: string;
  icon?: string;
}

interface Step {
  id: string;
  section?: string;
  question?: string;
  subtitle?: string;
  type: StepType;
  options?: Option[];
  placeholder?: string;
  tipNumber?: number;
  tipIcon?: string;
  tipLabel?: string;
  tipText?: string;
}

// ── Tips ─────────────────────────────────────────────────────────────────────

const tips_es = [
  { icon: '🗺️', label: '¿Sabías que...?', text: 'Panamá es mucho más que Ciudad de Panamá. Puedes encontrar estilos de vida completamente diferentes entre la capital, las playas del Pacífico, las montañas de Chiriquí y las islas del Caribe.' },
  { icon: '🚗', label: 'Dato curioso', text: 'Panamá es relativamente pequeño. Puedes recorrer gran parte del país en carro y pasar de zonas urbanas a playas, montañas o áreas rurales durante el mismo viaje.' },
  { icon: '☕', label: 'Panamá en 10 segundos', text: 'Boquete, en la provincia de Chiriquí, es famoso internacionalmente por su café de especialidad y por su clima más fresco — muy distinto al calor de la capital.' },
  { icon: '🏝️', label: '¿Te imaginabas esto de Panamá?', text: 'Panamá tiene islas tanto en el Caribe como en el Pacífico. Desde Bocas del Toro y Guna Yala hasta las islas del Pacífico — dos mundos costeros completamente diferentes.' },
  { icon: '✈️', label: '¿Sabías que...?', text: 'La posición geográfica de Panamá lo convierte en un punto estratégico para viajar por América. Para muchos residentes, el aeropuerto de Tocumen — con conexiones directas a más de 90 destinos — es una gran ventaja.' },
  { icon: '🌊', label: 'Algo que quizá no sabías', text: 'Panamá tiene costa en el Pacífico y en el Caribe. Eso significa dos tipos de playa, dos climas y dos experiencias completamente distintas sin salir del país.' },
  { icon: '🌴', label: 'Dato curioso', text: 'En Panamá puedes elegir entre vida urbana, playa, montaña, islas o comunidades tranquilas — todo dentro del mismo país. Muchas personas terminan viviendo en un lugar diferente al que imaginaron al principio.' },
  { icon: '💵', label: 'Panamá en 10 segundos', text: 'Panamá utiliza el dólar estadounidense como moneda de uso cotidiano. Sin tipo de cambio, sin riesgo cambiario — lo que simplifica mucho la planificación financiera para quienes vienen del mundo anglosajón.' },
];

const tips_en = [
  { icon: '🗺️', label: 'Did you know?', text: 'Panama is much more than Panama City. You can find completely different lifestyles between the capital, the Pacific beaches, the mountains of Chiriquí, and the Caribbean islands.' },
  { icon: '🚗', label: 'Fun fact', text: 'Panama is a relatively small country. You can drive across much of it in a single day, moving from urban areas to beaches, mountains, or rural landscapes during the same trip.' },
  { icon: '☕', label: 'Panama in 10 seconds', text: 'Boquete, in the Chiriquí highlands, is internationally known for its specialty coffee and cooler climate — very different from the heat of the capital.' },
  { icon: '🏝️', label: 'Did you picture this?', text: 'Panama has islands on both the Caribbean and Pacific coasts. From Bocas del Toro and Guna Yala to the Pacific islands — two completely different coastal worlds.' },
  { icon: '✈️', label: 'Did you know?', text: "Panama's geographic position makes it a strategic hub for travel across the Americas. Tocumen International Airport offers direct connections to over 90 destinations — a major draw for frequent travelers." },
  { icon: '🌊', label: 'Something you might not know', text: 'Panama has coastline on both the Pacific and the Caribbean. That means two types of beach, two climates, and two completely different experiences without ever leaving the country.' },
  { icon: '🌴', label: 'Fun fact', text: "In Panama you can choose between urban life, beach, mountains, islands, or quieter communities — all within the same country. Many people end up living somewhere completely different from what they imagined at first." },
  { icon: '💵', label: 'Panama in 10 seconds', text: "Panama uses the US dollar in everyday life. No exchange rate, no currency risk — which makes financial planning much simpler for those coming from North America or Europe." },
];

const tips_fr = [
  { icon: '🗺️', label: 'Le saviez-vous ?', text: 'Le Panama est bien plus que Panama City. On y trouve des styles de vie très différents entre la capitale, les plages du Pacifique, les montagnes du Chiriquí et les îles des Caraïbes.' },
  { icon: '🚗', label: 'Le saviez-vous ?', text: 'Le Panama est un pays relativement petit. Vous pouvez traverser une grande partie du pays en voiture en une seule journée, passant de zones urbaines à des plages, des montagnes ou des paysages ruraux.' },
  { icon: '☕', label: 'Panama en 10 secondes', text: 'Boquete, dans les highlands du Chiriquí, est internationalement connue pour son café de spécialité et son climat plus frais — très différent de la chaleur de la capitale.' },
  { icon: '🏝️', label: 'Le saviez-vous ?', text: 'Le Panama possède des îles à la fois dans les Caraïbes et sur le Pacifique. De Bocas del Toro à Guna Yala en passant par les îles du Pacifique — deux mondes côtiers complètement différents.' },
  { icon: '✈️', label: 'Le saviez-vous ?', text: "La position géographique du Panama en fait un hub stratégique pour voyager dans les Amériques. L'aéroport de Tocumen propose des vols directs vers plus de 90 destinations." },
  { icon: '🌊', label: 'À savoir', text: 'Le Panama a des côtes à la fois sur le Pacifique et sur les Caraïbes — deux types de plage, deux ambiances et deux expériences totalement différentes sans quitter le pays.' },
  { icon: '🌴', label: 'Le saviez-vous ?', text: "Au Panama, vous pouvez choisir entre vie urbaine, plage, montagne, îles ou communautés tranquilles — tout dans le même pays. Beaucoup finissent par vivre dans un endroit différent de ce qu'ils imaginaient au départ." },
  { icon: '💵', label: 'Panama en 10 secondes', text: 'Le Panama utilise le dollar américain au quotidien. Pas de taux de change, pas de risque de change — ce qui simplifie considérablement la planification financière.' },
];

// ── Steps builder ─────────────────────────────────────────────────────────────

function buildSteps(lang: Lang, tips: { icon: string; label: string; text: string }[]): Step[] {
  const t = (es: string, en: string, fr: string) => lang === 'es' ? es : lang === 'en' ? en : fr;

  const tip = (n: number): Step => ({
    id: `tip_${n}`,
    type: 'tip',
    tipNumber: n,
    tipIcon: tips[n - 1].icon,
    tipLabel: tips[n - 1].label,
    tipText: tips[n - 1].text,
  });

  return [
    // Q1
    {
      id: 'proposito',
      section: t('Tu intención', 'Your intention', 'Votre intention'),
      question: t('¿Qué te atrae principalmente de la idea de vivir en Panamá?', 'What mainly attracts you about the idea of living in Panama?', "Qu'est-ce qui vous attire principalement dans l'idée de vivre au Panama ?"),
      type: 'radio',
      options: [
        { value: 'estilo_vida', label: t('Un nuevo estilo de vida', 'A new lifestyle', 'Un nouveau style de vie'), icon: '🌴' },
        { value: 'playa', label: t('Vivir cerca de la playa', 'Living near the beach', 'Vivre près de la plage'), icon: '🏖️' },
        { value: 'jubilacion', label: t('Disfrutar mi jubilación', 'Enjoying my retirement', 'Profiter de ma retraite'), icon: '🌅' },
        { value: 'trabajo', label: t('Trabajar o emprender', 'Working or entrepreneurship', 'Travailler ou entreprendre'), icon: '💼' },
        { value: 'inversion', label: t('Invertir', 'Investing', 'Investir'), icon: '📈' },
        { value: 'segunda_residencia', label: t('Tener una segunda residencia', 'Having a second home', 'Avoir une résidence secondaire'), icon: '🏡' },
        { value: 'temporadas', label: t('Pasar temporadas en Panamá', 'Spending time in Panama seasonally', 'Passer des saisons au Panama'), icon: '✈️' },
        { value: 'explorando', label: t('Todavía estoy explorando', 'Still exploring', "J'explore encore"), icon: '🔎' },
      ],
    },
    // Q2
    {
      id: 'destino',
      section: t('Tu intención', 'Your intention', 'Votre intention'),
      question: t('Imagina que mañana despiertas en Panamá. ¿Qué paisaje preferirías ver?', 'Imagine you wake up in Panama tomorrow. What landscape would you prefer?', "Imaginez que vous vous réveillez au Panama demain. Quel paysage préféreriez-vous voir ?"),
      type: 'radio',
      options: [
        { value: 'oceano', label: t('El océano desde mi ventana', 'The ocean from my window', "L'océan depuis ma fenêtre"), icon: '🌊' },
        { value: 'montanas', label: t('Montañas y naturaleza', 'Mountains and nature', 'Montagnes et nature'), icon: '🌿' },
        { value: 'ciudad', label: t('Una ciudad moderna', 'A modern city', 'Une ville moderne'), icon: '🌆' },
        { value: 'comunidad', label: t('Una comunidad tranquila', 'A quiet community', 'Une communauté tranquille'), icon: '🏡' },
        { value: 'tropical', label: t('Palmeras y ambiente tropical', 'Palm trees and tropical vibe', 'Palmiers et ambiance tropicale'), icon: '🌴' },
        { value: 'pueblo', label: t('Un pequeño pueblo con cafés y restaurantes', 'A small town with cafés and restaurants', 'Un petit village avec cafés et restaurants'), icon: '☕' },
        { value: 'no_se', label: t('No estoy seguro todavía', "I'm not sure yet", "Je ne suis pas encore sûr(e)"), icon: '🤷' },
      ],
    },
    // Q3
    {
      id: 'ritmo',
      section: t('Tu intención', 'Your intention', 'Votre intention'),
      question: t('¿Cómo te gustaría que fueran tus días en Panamá?', 'How would you like your days in Panama to be?', 'Comment aimeriez-vous que vos journées se passent au Panama ?'),
      type: 'radio',
      options: [
        { value: 'tranquilo', label: t('Tranquilos y relajados', 'Peaceful and relaxed', 'Paisibles et détendus'), icon: '🧘' },
        { value: 'activo', label: t('Activos y llenos de aventuras', 'Active and full of adventures', "Actifs et pleins d'aventures"), icon: '🏄' },
        { value: 'playa_tranquila', label: t('Una combinación de playa y tranquilidad', 'A mix of beach and calm', 'Un mélange de plage et de tranquillité'), icon: '🌴' },
        { value: 'urbano', label: t('Dinámicos y urbanos', 'Dynamic and urban', 'Dynamiques et urbains'), icon: '🌆' },
        { value: 'social', label: t('Relajados, con restaurantes, cafés y actividades', 'Relaxed with restaurants, cafés and activities', 'Détendus avec restaurants, cafés et activités'), icon: '☕' },
        { value: 'viajero', label: t('Viajar y explorar constantemente', 'Traveling and exploring constantly', 'Voyager et explorer en permanence'), icon: '✈️' },
      ],
    },
    tip(1),
    // Q4
    {
      id: 'nacionalidad',
      section: t('Tu situación', 'Your situation', 'Votre situation'),
      question: t('¿Cuál es tu nacionalidad?', 'What is your nationality?', 'Quelle est votre nationalité ?'),
      type: 'radio',
      options: [
        { value: 'usa', label: 'Estados Unidos / USA', icon: '🇺🇸' },
        { value: 'canada', label: 'Canadá / Canada', icon: '🇨🇦' },
        { value: 'uk', label: 'Reino Unido / UK', icon: '🇬🇧' },
        { value: 'australia', label: 'Australia / Nueva Zelanda', icon: '🇦🇺' },
        { value: 'europa', label: t('Europa', 'Europe', 'Europe'), icon: '🇪🇺' },
        { value: 'latam', label: t('Latinoamérica', 'Latin America', 'Amérique latine'), icon: '🌎' },
        { value: 'otro', label: t('Otro país', 'Another country', 'Autre pays'), icon: '🌍' },
      ],
    },
    // Q5
    {
      id: 'ingresos',
      section: t('Tu situación', 'Your situation', 'Votre situation'),
      question: t('¿Cuál describe mejor tu fuente principal de ingresos?', 'Which best describes your main source of income?', 'Quelle est votre principale source de revenus ?'),
      type: 'radio',
      options: [
        { value: 'pension', label: t('Pensión / jubilación', 'Pension / retirement', 'Retraite / pension'), icon: '🏖️' },
        { value: 'remoto', label: t('Trabajo remoto', 'Remote work', 'Télétravail'), icon: '💻' },
        { value: 'empleo', label: t('Empleo profesional', 'Professional employment', 'Emploi professionnel'), icon: '💼' },
        { value: 'negocio', label: t('Negocio propio', 'Own business', 'Propre entreprise'), icon: '🚀' },
        { value: 'inversiones', label: t('Inversiones', 'Investments', 'Investissements'), icon: '📈' },
        { value: 'ahorros', label: t('Ahorros / patrimonio', 'Savings / assets', 'Épargne / patrimoine'), icon: '💰' },
        { value: 'combinacion', label: t('Una combinación de varias', 'A combination', 'Une combinaison'), icon: '🔀' },
      ],
    },
    // Q6
    {
      id: 'presupuesto_vida',
      section: t('Tu situación', 'Your situation', 'Votre situation'),
      question: t('¿Qué presupuesto mensual te gustaría destinar aproximadamente a tu vida en Panamá?', 'What monthly budget would you like to allocate for your life in Panama?', 'Quel budget mensuel souhaiteriez-vous consacrer à votre vie au Panama ?'),
      type: 'radio',
      options: [
        { value: 'menos_1500', label: t('Menos de $1,500', 'Less than $1,500', 'Moins de 1 500 $'), icon: '💵' },
        { value: '1500_2500', label: '$1,500 – $2,500', icon: '💰' },
        { value: '2500_4000', label: '$2,500 – $4,000', icon: '💼' },
        { value: '4000_6000', label: '$4,000 – $6,000', icon: '✨' },
        { value: 'mas_6000', label: t('Más de $6,000', 'More than $6,000', 'Plus de 6 000 $'), icon: '💎' },
        { value: 'no_se', label: t('Todavía no lo sé', "I don't know yet", 'Je ne sais pas encore'), icon: '🤷' },
      ],
    },
    tip(2),
    // Q7
    {
      id: 'ubicacion',
      section: t('Dónde vivir', 'Where to live', 'Où vivre'),
      question: t('¿Dónde te sentirías más cómodo viviendo?', 'Where would you feel most comfortable living?', "Où vous sentiriez-vous le plus à l'aise ?"),
      type: 'radio',
      options: [
        { value: 'mar', label: t('Cerca del mar', 'Near the sea', 'Près de la mer'), icon: '🌊' },
        { value: 'montana', label: t('En las montañas', 'In the mountains', 'En montagne'), icon: '🏔️' },
        { value: 'ciudad', label: t('En una ciudad', 'In a city', 'En ville'), icon: '🌆' },
        { value: 'naturaleza', label: t('Rodeado de naturaleza', 'Surrounded by nature', 'Entouré(e) de nature'), icon: '🌿' },
        { value: 'residencial', label: t('En una comunidad residencial', 'In a residential community', 'Dans une communauté résidentielle'), icon: '🏡' },
        { value: 'pueblo', label: t('En un pueblo pequeño', 'In a small town', 'Dans un petit village'), icon: '☕' },
        { value: 'varios', label: t('Me gustaría probar diferentes lugares', "I'd like to try different places", "J'aimerais essayer différents endroits"), icon: '🔄' },
      ],
    },
    // Q8
    {
      id: 'actividades',
      section: t('Dónde vivir', 'Where to live', 'Où vivre'),
      question: t('¿Qué cosas te gustaría tener cerca de casa?', 'What would you like to have close to home?', "Qu'aimeriez-vous avoir près de chez vous ?"),
      subtitle: t('Selecciona todas las que quieras', 'Select all that apply', 'Sélectionnez tout ce qui vous convient'),
      type: 'multiselect',
      options: [
        { value: 'surf', label: 'Surf', icon: '🏄' },
        { value: 'golf', label: 'Golf', icon: '⛳' },
        { value: 'senderismo', label: t('Senderismo', 'Hiking', 'Randonnée'), icon: '🥾' },
        { value: 'pesca', label: t('Pesca', 'Fishing', 'Pêche'), icon: '🎣' },
        { value: 'yoga', label: t('Yoga y bienestar', 'Yoga & wellness', 'Yoga et bien-être'), icon: '🧘' },
        { value: 'cafes', label: 'Cafés', icon: '☕' },
        { value: 'restaurantes', label: t('Restaurantes', 'Restaurants', 'Restaurants'), icon: '🍽️' },
        { value: 'vida_nocturna', label: t('Vida nocturna', 'Nightlife', 'Vie nocturne'), icon: '🍸' },
        { value: 'compras', label: t('Compras', 'Shopping', 'Shopping'), icon: '🛍️' },
        { value: 'arte', label: t('Arte y cultura', 'Art & culture', 'Art et culture'), icon: '🎭' },
        { value: 'naturaleza', label: t('Naturaleza', 'Nature', 'Nature'), icon: '🌿' },
        { value: 'playa', label: t('Playa', 'Beach', 'Plage'), icon: '🏖️' },
        { value: 'buceo', label: t('Buceo / snorkel', 'Diving / snorkel', 'Plongée / snorkel'), icon: '🐠' },
      ],
    },
    // Q9
    {
      id: 'vivienda',
      section: t('Dónde vivir', 'Where to live', 'Où vivre'),
      question: t("¿Qué tipo de vivienda encaja más con lo que estás buscando?", "What type of housing fits best with what you're looking for?", "Quel type de logement correspond le mieux à ce que vous recherchez ?"),
      type: 'radio',
      options: [
        { value: 'apartamento', label: t('Apartamento', 'Apartment', 'Appartement'), icon: '🏢' },
        { value: 'casa', label: t('Casa', 'House', 'Maison'), icon: '🏡' },
        { value: 'frente_mar', label: t('Propiedad frente al mar', 'Beachfront property', 'Propriété en bord de mer'), icon: '🌊' },
        { value: 'montana', label: t('Casa en la montaña', 'Mountain house', 'Maison en montagne'), icon: '🌿' },
        { value: 'comunidad', label: t('Comunidad residencial', 'Residential community', 'Communauté résidentielle'), icon: '🏘️' },
        { value: 'terreno', label: t('Terreno para construir', 'Land to build', 'Terrain à construire'), icon: '🏗️' },
        { value: 'alquiler', label: t('Primero quiero alquilar', 'I want to rent first', "Je veux d'abord louer"), icon: '🔑' },
        { value: 'ver_opciones', label: t('Necesito conocer mis opciones', 'I need to know my options', "J'ai besoin de connaître mes options"), icon: '🤷' },
      ],
    },
    tip(3),
    // Q10
    {
      id: 'compra_alquiler',
      section: t('Tu vivienda', 'Your housing', 'Votre logement'),
      question: t('¿Qué te gustaría hacer con tu vivienda en Panamá?', 'What would you like to do with your housing in Panama?', 'Que souhaiteriez-vous faire avec votre logement au Panama ?'),
      type: 'radio',
      options: [
        { value: 'comprar_vivir', label: t('Comprar para vivir', 'Buy to live', 'Acheter pour habiter'), icon: '🏠' },
        { value: 'alquilar', label: t('Alquilar inicialmente', 'Rent initially', "Louer dans un premier temps"), icon: '🔑' },
        { value: 'comprar_inversion', label: t('Comprar como inversión', 'Buy as investment', "Acheter comme investissement"), icon: '📈' },
        { value: 'segunda_residencia', label: t('Comprar una segunda residencia', 'Buy a second home', "Acheter une résidence secondaire"), icon: '🌴' },
        { value: 'vacacional', label: t('Tener una propiedad para vacaciones', 'Have a vacation property', "Avoir une propriété de vacances"), icon: '🏡' },
        { value: 'alquilar_luego_comprar', label: t('Alquilar primero y comprar después', 'Rent first then buy', "Louer d'abord puis acheter"), icon: '🔄' },
        { value: 'no_propiedad', label: t('No estoy interesado en propiedad', 'Not interested in property', "Pas intéressé(e) par la propriété"), icon: '❌' },
      ],
    },
    // Q11
    {
      id: 'plazo',
      section: t('Tu vivienda', 'Your housing', 'Votre logement'),
      question: t('¿Cuándo te gustaría comenzar tu nueva vida en Panamá?', 'When would you like to start your new life in Panama?', 'Quand aimeriez-vous commencer votre nouvelle vie au Panama ?'),
      type: 'radio',
      options: [
        { value: '3_meses', label: t('En los próximos 3 meses', 'In the next 3 months', 'Dans les 3 prochains mois'), icon: '🚀' },
        { value: '3_6', label: t('3 – 6 meses', '3 – 6 months', '3 – 6 mois'), icon: '📅' },
        { value: '6_12', label: t('6 – 12 meses', '6 – 12 months', '6 – 12 mois'), icon: '🗓️' },
        { value: '1_2', label: t('1 – 2 años', '1 – 2 years', '1 – 2 ans'), icon: '🔮' },
        { value: 'mas_adelante', label: t('Más adelante', 'Further down the road', 'Plus tard'), icon: '🌎' },
        { value: 'investigando', label: t('Solo estoy investigando', 'Just researching', "Je fais juste des recherches"), icon: '👀' },
      ],
    },
    // Q12
    {
      id: 'familia',
      section: t('Tu vivienda', 'Your housing', 'Votre logement'),
      question: t('¿Quién estaría viviendo contigo en Panamá?', 'Who would be living with you in Panama?', 'Qui vivrait avec vous au Panama ?'),
      subtitle: t('Selecciona todas las que apliquen', 'Select all that apply', "Sélectionnez tout ce qui s'applique"),
      type: 'multiselect',
      options: [
        { value: 'solo', label: t('Solo yo', 'Just me', 'Seulement moi'), icon: '🧑' },
        { value: 'pareja', label: t('Mi pareja / esposo(a)', 'My partner / spouse', 'Mon(ma) partenaire'), icon: '👫' },
        { value: 'hijos', label: t('Hijos', 'Children', 'Enfants'), icon: '👨‍👩‍👧' },
        { value: 'familiares', label: t('Otros familiares', 'Other family members', "D'autres membres de la famille"), icon: '👵' },
        { value: 'mascotas', label: t('Mascotas', 'Pets', 'Animaux de compagnie'), icon: '🐕' },
        { value: 'combinacion', label: t('Una combinación de varios', 'A combination', 'Une combinaison'), icon: '👨‍👩‍👧‍👦' },
      ],
    },
    tip(4),
    // Q13
    {
      id: 'espanol',
      section: t('Tu día a día', 'Your daily life', 'Votre quotidien'),
      question: t('¿Cómo te sientes comunicándote en español?', 'How comfortable are you communicating in Spanish?', "Comment vous sentez-vous pour communiquer en espagnol ?"),
      type: 'radio',
      options: [
        { value: 'fluido', label: t('Hablo español con fluidez', 'I speak Spanish fluently', "Je parle espagnol couramment"), icon: '🇪🇸' },
        { value: 'conversacional', label: t('Puedo mantener conversaciones', 'I can hold conversations', "Je peux tenir des conversations"), icon: '💬' },
        { value: 'basico', label: t('Entiendo algunas cosas', 'I understand some things', 'Je comprends quelques mots'), icon: '👋' },
        { value: 'empezando', label: t('Estoy comenzando', "I'm just starting", 'Je commence'), icon: '🤓' },
        { value: 'nada', label: t('No hablo español', "I don't speak Spanish", "Je ne parle pas espagnol"), icon: '🌎' },
      ],
    },
    // Q14
    {
      id: 'ciudad_tranquilidad',
      section: t('Tu día a día', 'Your daily life', 'Votre quotidien'),
      question: t('¿Qué tan importante es tener servicios y entretenimiento cerca?', 'How important is it to have services and entertainment nearby?', "Quelle importance accordez-vous aux services et aux loisirs à proximité ?"),
      type: 'radio',
      options: [
        { value: 'muy_importante', label: t('Muy importante — quiero tenerlo todo cerca', 'Very important — I want everything nearby', 'Très important — je veux tout à proximité'), icon: '🏙️' },
        { value: 'importante', label: t('Importante — restaurantes, tiendas y servicios', 'Important — restaurants, shops and services', 'Important — restaurants, commerces et services'), icon: '🍽️' },
        { value: 'tranquilidad', label: t('Prefiero tranquilidad aunque esté más lejos', 'I prefer calm even if further away', "Je préfère le calme même si c'est plus loin"), icon: '🌿' },
        { value: 'naturaleza', label: t("Prefiero sacrificar servicios por vivir cerca de la naturaleza", "I'd rather sacrifice services for nature", 'Je préfère sacrifier des services pour la nature'), icon: '🏖️' },
        { value: 'equilibrio', label: t('Quiero un equilibrio', 'I want a balance', "Je veux un équilibre"), icon: '⚖️' },
      ],
    },
    // Q15
    {
      id: 'conectividad',
      section: t('Tu día a día', 'Your daily life', 'Votre quotidien'),
      question: t('¿Qué tan importante es para ti poder viajar fácilmente desde Panamá?', 'How important is it to be able to travel easily from Panama?', "Quelle importance accordez-vous à la facilité de voyager depuis le Panama ?"),
      type: 'radio',
      options: [
        { value: 'muy_importante', label: t('Muy importante — viajo frecuentemente', 'Very important — I travel frequently', 'Très important — je voyage fréquemment'), icon: '✈️' },
        { value: 'importante', label: t('Importante — quiero buena conexión internacional', 'Important — I want good international connections', 'Important — je veux de bonnes connexions internationales'), icon: '🌎' },
        { value: 'no_prioridad', label: t('No es una prioridad', "It's not a priority", "Ce n'est pas une priorité"), icon: '🏡' },
        { value: 'ocasional', label: t('Viajaré ocasionalmente', "I'll travel occasionally", 'Je voyagerai occasionnellement'), icon: '🔄' },
      ],
    },
    tip(5),
    // Q16
    {
      id: 'trabajo',
      section: t('Trabajo y finanzas', 'Work & finances', 'Travail et finances'),
      question: t('Si vivieras en Panamá, ¿qué papel tendría el trabajo en tu día a día?', 'If you lived in Panama, what role would work play in your daily life?', 'Si vous viviez au Panama, quel rôle le travail jouerait-il dans votre quotidien ?'),
      type: 'radio',
      options: [
        { value: 'no_trabajo', label: t('No planeo trabajar', "I don't plan to work", 'Je ne prévois pas de travailler'), icon: '🏖️' },
        { value: 'remoto', label: t('Continuaría trabajando remotamente', 'I would continue working remotely', 'Je continuerais à travailler à distance'), icon: '💻' },
        { value: 'negocio_propio', label: t('Manejaría mi propio negocio', 'I would run my own business', 'Je gérerais ma propre entreprise'), icon: '🚀' },
        { value: 'negocio_panama', label: t('Quiero desarrollar un negocio en Panamá', 'I want to develop a business in Panama', 'Je veux développer une entreprise au Panama'), icon: '🏢' },
        { value: 'local', label: t('Me interesa trabajar localmente', "I'm interested in working locally", "Je suis intéressé(e) par le travail local"), icon: '💼' },
        { value: 'no_seguro', label: t('Todavía no estoy seguro', "I'm not sure yet", "Je ne suis pas encore sûr(e)"), icon: '🔄' },
      ],
    },
    // Q17
    {
      id: 'valores_hogar',
      section: t('Trabajo y finanzas', 'Work & finances', 'Travail et finances'),
      question: t('¿Qué es lo que más valoras en tu hogar?', 'What do you value most in your home?', "Qu'est-ce que vous appréciez le plus dans votre logement ?"),
      subtitle: t('Selecciona tus prioridades', 'Select your priorities', 'Sélectionnez vos priorités'),
      type: 'multiselect',
      options: [
        { value: 'ubicacion', label: t('Una excelente ubicación', 'An excellent location', 'Un emplacement excellent'), icon: '🌊' },
        { value: 'espacio', label: t('Espacio y privacidad', 'Space and privacy', 'Espace et intimité'), icon: '🏡' },
        { value: 'amenidades', label: t('Piscina y amenidades', 'Pool and amenities', 'Piscine et commodités'), icon: '🏊' },
        { value: 'naturaleza', label: t('Naturaleza', 'Nature', 'Nature'), icon: '🌿' },
        { value: 'servicios', label: t('Cercanía a servicios', 'Proximity to services', 'Proximité des services'), icon: '🏙️' },
        { value: 'seguridad', label: t('Seguridad', 'Security', 'Sécurité'), icon: '🔐' },
        { value: 'diseno', label: t('Diseño y comodidad', 'Design and comfort', 'Design et confort'), icon: '✨' },
        { value: 'precio', label: t('Buena relación calidad-precio', 'Good value for money', 'Bon rapport qualité-prix'), icon: '💰' },
      ],
    },
    // Q18
    {
      id: 'experiencia',
      section: t('Trabajo y finanzas', 'Work & finances', 'Travail et finances'),
      question: t('¿Cuál de estas experiencias te gustaría vivir durante tu primer año en Panamá?', 'Which of these experiences would you like to have during your first year in Panama?', 'Laquelle de ces expériences aimeriez-vous vivre lors de votre première année au Panama ?'),
      subtitle: t('Selecciona todas las que te gusten', 'Select all you like', 'Sélectionnez tout ce qui vous plaît'),
      type: 'multiselect',
      options: [
        { value: 'islas', label: t('Explorar las islas', 'Explore the islands', 'Explorer les îles'), icon: '🏝️' },
        { value: 'road_trip', label: t('Hacer road trips por el país', 'Do road trips around the country', 'Faire des road trips dans le pays'), icon: '🚗' },
        { value: 'cafe_montana', label: t('Conocer las montañas y el café', 'Discover the mountains and coffee', 'Découvrir les montagnes et le café'), icon: '☕' },
        { value: 'playa', label: t('Vivir cerca de la playa', 'Live near the beach', 'Vivre près de la plage'), icon: '🌊' },
        { value: 'ciudad', label: t('Descubrir Ciudad de Panamá', 'Discover Panama City', 'Découvrir Panama City'), icon: '🌆' },
        { value: 'naturaleza', label: t('Explorar parques y naturaleza', 'Explore parks and nature', 'Explorer les parcs et la nature'), icon: '🌿' },
        { value: 'gastronomia', label: t('Conocer la gastronomía local', 'Discover local gastronomy', 'Découvrir la gastronomie locale'), icon: '🍽️' },
        { value: 'todo', label: t('Un poco de todo', 'A bit of everything', 'Un peu de tout'), icon: '🔄' },
      ],
    },
    tip(6),
    // Q19
    {
      id: 'visita',
      section: t('¡Casi listo!', 'Almost there!', 'Presque terminé !'),
      question: t('¿Cuál ha sido tu relación con Panamá hasta ahora?', 'What has been your relationship with Panama so far?', "Quelle a été votre relation avec le Panama jusqu'à présent ?"),
      type: 'radio',
      options: [
        { value: 'encanto', label: t('Ya lo visité y quiero volver', 'I visited and want to go back', "Je l'ai visité et je veux y retourner"), icon: '❤️' },
        { value: 'varias', label: t('Lo he visitado varias veces', "I've visited several times", "Je l'ai visité plusieurs fois"), icon: '🌴' },
        { value: 'evaluando', label: t('Lo visité y todavía estoy evaluando', "I visited and I'm still evaluating", "Je l'ai visité et j'évalue encore"), icon: '🔍' },
        { value: 'pronto', label: t('Tengo un viaje próximamente', 'I have a trip coming up', "J'ai un voyage prévu prochainement"), icon: '✈️' },
        { value: 'nunca', label: t('Nunca he estado', "I've never been", "Je n'y suis jamais allé(e)"), icon: '👋' },
        { value: 'investigando', label: t('Apenas estoy comenzando a investigar', "I'm just starting to research", "Je commence tout juste à me renseigner"), icon: '👀' },
      ],
    },
    // Q20
    {
      id: 'prioridad',
      section: t('¡Casi listo!', 'Almost there!', 'Presque terminé !'),
      question: t('Si tuvieras que elegir UNA sola cosa para tu vida en Panamá, ¿cuál sería?', 'If you had to choose ONE single thing for your life in Panama, what would it be?', "Si vous deviez choisir UNE seule chose pour votre vie au Panama, laquelle serait-ce ?"),
      type: 'radio',
      options: [
        { value: 'calidad_vida', label: t('Calidad de vida', 'Quality of life', 'Qualité de vie'), icon: '🌴' },
        { value: 'clima_playa', label: t('Clima y playa', 'Climate and beach', 'Climat et plage'), icon: '🏖️' },
        { value: 'tranquilidad', label: t('Tranquilidad', 'Tranquility', 'Tranquillité'), icon: '🧘' },
        { value: 'costo', label: t('Costo de vida', 'Cost of living', 'Coût de la vie'), icon: '💰' },
        { value: 'profesional', label: t('Oportunidades profesionales', 'Professional opportunities', 'Opportunités professionnelles'), icon: '💼' },
        { value: 'inversion_fin', label: t('Inversión', 'Investment', 'Investissement'), icon: '📈' },
        { value: 'comunidad', label: t('Comunidad', 'Community', 'Communauté'), icon: '🤝' },
        { value: 'libertad', label: t('Libertad para viajar', 'Freedom to travel', 'Liberté de voyager'), icon: '✈️' },
        { value: 'experiencia', label: t('Una nueva experiencia', 'A new experience', 'Une nouvelle expérience'), icon: '🌎' },
      ],
    },
    // Q21
    {
      id: 'preocupacion',
      section: t('¡Casi listo!', 'Almost there!', 'Presque terminé !'),
      question: t('¿Qué es lo que más quieres resolver antes de mudarte?', 'What do you most want to resolve before moving?', "Qu'est-ce que vous souhaitez le plus résoudre avant de déménager ?"),
      subtitle: t('Selecciona todas las que apliquen', 'Select all that apply', "Sélectionnez tout ce qui s'applique"),
      type: 'multiselect',
      options: [
        { value: 'residencia', label: t('Mi residencia', 'My residency', 'Ma résidence'), icon: '🛂' },
        { value: 'vivienda', label: t('Encontrar dónde vivir', 'Finding where to live', 'Trouver où vivre'), icon: '🏠' },
        { value: 'banca', label: t('Banca', 'Banking', 'Services bancaires'), icon: '🏦' },
        { value: 'impuestos', label: t('Impuestos', 'Taxes', 'Impôts'), icon: '💰' },
        { value: 'salud', label: t('Salud y seguro', 'Health and insurance', 'Santé et assurance'), icon: '🏥' },
        { value: 'transporte', label: t('Transporte', 'Transportation', 'Transport'), icon: '🚗' },
        { value: 'idioma', label: t('Idioma', 'Language', 'Langue'), icon: '🗣️' },
        { value: 'familia', label: t('Adaptación de mi familia', "My family's adaptation", "L'adaptation de ma famille"), icon: '👨‍👩‍👧' },
        { value: 'proceso', label: t('Organizar todo el proceso', 'Organizing the whole process', 'Organiser tout le processus'), icon: '📋' },
      ],
    },
    tip(7),
    // Q22
    {
      id: 'acompanamiento',
      section: t('Últimos pasos', 'Last steps', 'Dernières étapes'),
      question: t('¿Cómo te gustaría recibir ayuda para planificar tu llegada?', 'How would you like to receive help planning your arrival?', "Comment aimeriez-vous recevoir de l'aide pour planifier votre arrivée ?"),
      type: 'radio',
      options: [
        { value: 'todo', label: t('Quiero que me ayuden con todo', 'I want help with everything', "Je veux de l'aide pour tout"), icon: '🙌' },
        { value: 'paso_a_paso', label: t('Quiero asesoramiento paso a paso', 'I want step-by-step guidance', 'Je veux un accompagnement étape par étape'), icon: '🤝' },
        { value: 'investigar', label: t('Prefiero investigar primero', 'I prefer to research first', "Je préfère d'abord faire des recherches"), icon: '📚' },
        { value: 'opciones', label: t('Solo quiero conocer mis opciones', 'I just want to know my options', 'Je veux juste connaître mes options'), icon: '🔎' },
        { value: 'hablar', label: t("Me gustaría hablar con alguien", "I'd like to speak with someone", "J'aimerais parler à quelqu'un"), icon: '📞' },
      ],
    },
    // Q23
    {
      id: 'resultado_esperado',
      section: t('Últimos pasos', 'Last steps', 'Dernières étapes'),
      question: t('¿Qué te gustaría descubrir con Tu Ruta?', 'What would you like to discover with Your Route?', "Qu'aimeriez-vous découvrir avec Votre Route ?"),
      subtitle: t('Selecciona todas las que apliquen', 'Select all that apply', "Sélectionnez tout ce qui s'applique"),
      type: 'multiselect',
      options: [
        { value: 'residencia', label: t('Qué opciones de residencia podrían encajar conmigo', 'Which residency options might suit me', 'Quelles options de résidence pourraient me convenir'), icon: '🛂' },
        { value: 'zonas', label: t('Qué zonas de Panamá podrían ser ideales para mí', 'Which areas of Panama might be ideal for me', "Quelles zones du Panama pourraient m'être idéales"), icon: '🏡' },
        { value: 'presupuesto', label: t('Qué estilo de vida puedo tener según mi presupuesto', 'What lifestyle I can have with my budget', 'Quel style de vie je peux avoir selon mon budget'), icon: '💰' },
        { value: 'inmobiliario', label: t('Qué opciones inmobiliarias podrían interesarme', 'Which real estate options might interest me', "Quelles options immobilières pourraient m'intéresser"), icon: '🏠' },
        { value: 'vida', label: t('Qué tipo de vida en Panamá encaja conmigo', 'What type of life in Panama suits me', 'Quel type de vie au Panama me convient'), icon: '🌴' },
        { value: 'consideraciones', label: t('Qué debería considerar antes de mudarme', 'What I should consider before moving', 'Ce que je devrais considérer avant de déménager'), icon: '📋' },
        { value: 'todo', label: t('Quiero conocer todo lo anterior', 'I want to know all of the above', 'Je veux tout savoir'), icon: '🔎' },
      ],
    },
    // Q24
    {
      id: 'notas',
      section: t('Últimos pasos', 'Last steps', 'Dernières étapes'),
      question: t('Antes de mostrarte tu perfil, ¿hay algo que quieras contarnos? (Opcional)', 'Before we show your profile, is there anything you want to tell us? (Optional)', "Avant de vous montrer votre profil, y a-t-il quelque chose que vous souhaitez nous dire ? (Facultatif)"),
      type: 'textarea',
      placeholder: t(
        'Cuéntanos sobre tus planes, expectativas, dudas o algo específico que estés buscando en Panamá...',
        "Tell us about your plans, expectations, questions or something specific you're looking for in Panama...",
        'Parlez-nous de vos plans, attentes, questions ou de quelque chose de spécifique que vous recherchez au Panama...'
      ),
    },
    tip(8),
    // Q25
    {
      id: 'contacto',
      section: t('Últimos pasos', 'Last steps', 'Dernières étapes'),
      question: t('¿Quieres recibir tu perfil personalizado y la guía de Panamá?', 'Do you want to receive your personalized profile and the Panama guide?', "Souhaitez-vous recevoir votre profil personnalisé et le guide du Panama ?"),
      type: 'radio',
      options: [
        { value: 'email', label: t('Sí, envíamela por email', 'Yes, send it by email', 'Oui, envoyez-le par email'), icon: '📧' },
        { value: 'whatsapp', label: t('Sí, prefiero WhatsApp', 'Yes, I prefer WhatsApp', 'Oui, je préfère WhatsApp'), icon: '📱' },
        { value: 'llamada', label: t('Quiero que me contacten', 'I want to be contacted', 'Je veux être contacté(e)'), icon: '📞' },
        { value: 'agendar', label: t('Quiero agendar una conversación', 'I want to schedule a conversation', 'Je veux planifier une conversation'), icon: '📅' },
        { value: 'solo_resultado', label: t('Solo quiero ver mi resultado', 'I just want to see my result', 'Je veux juste voir mon résultat'), icon: '👍' },
      ],
    },
    // Form
    {
      id: 'datos',
      question: t('¿Dónde enviamos tu perfil personalizado?', 'Where should we send your personalized profile?', 'Où vous envoyer votre profil personnalisé ?'),
      type: 'form',
    },
  ];
}

// ── Result engine ─────────────────────────────────────────────────────────────

interface ResultData {
  heading: string;
  body: string;
  cta: string;
  ctaHref: string;
}

function buildResult(answers: Record<string, string | string[]>, lang: Lang): ResultData {
  const ingresos = answers['ingresos'] as string;
  const trabajo = answers['trabajo'] as string;
  const presupuesto = answers['presupuesto_vida'] as string;
  const proposito = answers['proposito'] as string;
  const compra = answers['compra_alquiler'] as string;

  const isRetired = ingresos === 'pension' || trabajo === 'no_trabajo';
  const isRemote = ingresos === 'remoto' || trabajo === 'remoto';
  const isInvestor = proposito === 'inversion' || compra === 'comprar_inversion' ||
    presupuesto === '4000_6000' || presupuesto === 'mas_6000';

  if (lang === 'en') {
    if (isRetired) return { heading: 'The Pensionado visa looks like the strongest fit.', body: 'As a retiree planning a full or part-time move, the Pensionado route offers permanent residency, 20% off medications, and dozens of legal discounts on everything from restaurants to flights.', cta: 'See Pensionado visa →', ctaHref: '/en/visas/pensionado' };
    if (isRemote) return { heading: 'Friendly Nations or Digital Nomad — depending on your passport.', body: 'Remote workers have two key routes: Friendly Nations (for residents of 50 countries with an economic tie to Panama) and Digital Nomad ($36,000/yr in foreign income, 9-month stay).', cta: 'Compare visas →', ctaHref: '/en/visas/comparison' };
    if (isInvestor) return { heading: 'Qualified Investor — fast-track permanent residency.', body: 'With an investment goal or higher budget, the Qualified Investor visa delivers permanent residency in 30–45 days via real estate, stocks, or a fixed deposit.', cta: 'See Qualified Investor →', ctaHref: '/en/visas/qualified-investor' };
    return { heading: 'A few routes could work for you.', body: "Based on your profile, we recommend comparing all residency routes side by side — exact requirements, timelines, and real costs for each situation.", cta: 'Compare all visas →', ctaHref: '/en/visas' };
  }

  if (lang === 'fr') {
    if (isRetired) return { heading: 'Le visa Pensionado est probablement le plus adapté.', body: "En tant que retraité(e) avec une pension régulière, la voie Pensionado offre la résidence permanente, 20 % de réduction sur les médicaments et des dizaines d'avantages légaux.", cta: 'Voir le visa Pensionado →', ctaHref: '/fr/visas/pensionado' };
    if (isRemote) return { heading: 'Nations Amies ou Nomade Digital — selon votre passeport.', body: "Les travailleurs à distance ont deux voies clés : Nations Amies (résidents de 50 pays avec lien économique au Panama) et Nomade Digital (36 000 $/an de revenus étrangers).", cta: 'Comparer les visas →', ctaHref: '/fr/visas/comparaison' };
    if (isInvestor) return { heading: "L'Investisseur Qualifié — résidence permanente rapide.", body: "Avec un budget élevé ou un objectif d'investissement, le visa Investisseur Qualifié offre la résidence permanente en 30 à 45 jours.", cta: "Voir l'Investisseur Qualifié →", ctaHref: '/fr/visas/investisseur-qualifie' };
    return { heading: 'Plusieurs voies pourraient vous convenir.', body: 'Sur la base de votre profil, nous vous recommandons de comparer toutes les voies de résidence côte à côte.', cta: 'Comparer tous les visas →', ctaHref: '/fr/visas' };
  }

  if (isRetired) return { heading: 'La Visa Pensionado parece la más adecuada para tu perfil.', body: 'Como jubilado o persona con ingresos recurrentes, la ruta Pensionado ofrece residencia permanente, 20% de descuento en medicamentos y docenas de beneficios legales.', cta: 'Ver visa Pensionado →', ctaHref: '/es/visas/pensionado' };
  if (isRemote) return { heading: 'Naciones Amigas o Nómada Digital — según tu pasaporte.', body: 'Los trabajadores remotos tienen dos rutas clave: Naciones Amigas y Nómada Digital (ingresos de $36,000/año del exterior, estadía 9 meses). Compáralas.', cta: 'Comparar visas →', ctaHref: '/es/visas/comparativa' };
  if (isInvestor) return { heading: 'Inversionista Calificado — residencia permanente acelerada.', body: 'Con un presupuesto alto o meta de inversión, la Visa Inversionista Calificado otorga residencia permanente en 30-45 días vía bienes raíces, acciones o depósito a plazo.', cta: 'Ver Inversionista Calificado →', ctaHref: '/es/visas/inversionista-calificado' };
  return { heading: 'Varias rutas podrían funcionar para ti.', body: 'Con base en tu perfil, te recomendamos comparar todas las rutas de residencia una al lado de la otra — requisitos exactos, tiempos y costos reales para cada situación.', cta: 'Comparar todas las visas →', ctaHref: '/es/visas' };
}

// ── UI labels ─────────────────────────────────────────────────────────────────

const uiLabels = {
  es: {
    back: '← Atrás', next: 'Continuar →',
    stepOf: (n: number, t: number) => `Paso ${n} de ${t}`,
    thanks: '¡Listo! Te enviamos tu perfil en los próximos minutos.',
    name: 'Tu nombre', email: 'Correo electrónico', phone: 'WhatsApp / Teléfono',
    namePh: 'Nombre', emailPh: 'tu@ejemplo.com', phonePh: '+1 (555) 123-4567',
    formNote: 'Enviaremos tu perfil personalizado a este correo. Sin spam, nunca.',
    formCta: 'Enviar mi perfil gratuito →',
    required: 'Campo requerido', result: 'Tu perfil',
    whatsapp: '¿Prefieres WhatsApp?',
    whatsappMsg: '¡Hola! Completé Tu Ruta de Beyond Panama Relocation y quiero más información sobre mudarme a Panamá.',
    tipContinue: 'Continuar →',
  },
  en: {
    back: '← Back', next: 'Continue →',
    stepOf: (n: number, t: number) => `Step ${n} of ${t}`,
    thanks: "Done! We'll send your profile in the next few minutes.",
    name: 'Your name', email: 'Email address', phone: 'WhatsApp / Phone',
    namePh: 'Name', emailPh: 'you@example.com', phonePh: '+1 (555) 123-4567',
    formNote: "We'll send your personalized profile to this email. No spam, ever.",
    formCta: 'Send my free profile →',
    required: 'Required field', result: 'Your profile',
    whatsapp: 'Prefer WhatsApp?',
    whatsappMsg: "Hi! I just completed the Beyond Panama Relocation route finder and would like more information about moving to Panama.",
    tipContinue: 'Continue →',
  },
  fr: {
    back: '← Retour', next: 'Continuer →',
    stepOf: (n: number, t: number) => `Étape ${n} sur ${t}`,
    thanks: 'Parfait ! Nous vous enverrons votre profil dans les prochaines minutes.',
    name: 'Votre nom', email: 'Adresse e-mail', phone: 'WhatsApp / Téléphone',
    namePh: 'Nom', emailPh: 'vous@exemple.com', phonePh: '+1 (555) 123-4567',
    formNote: 'Nous vous enverrons votre profil personnalisé à cet e-mail. Jamais de spam.',
    formCta: 'Envoyer mon profil gratuit →',
    required: 'Champ obligatoire', result: 'Votre profil',
    whatsapp: 'Vous préférez WhatsApp ?',
    whatsappMsg: "Bonjour ! Je viens de compléter le guide Beyond Panama Relocation et j'aimerais plus d'informations sur l'installation au Panama.",
    tipContinue: 'Continuer →',
  },
};

const fadeInStyle = `
@keyframes quizFadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.quiz-fade-in { animation: quizFadeIn 0.3s ease both; }
`;

// ── Component ─────────────────────────────────────────────────────────────────

export default function Quiz({ lang = 'es' }: { lang?: Lang }) {
  const tipsMap = { es: tips_es, en: tips_en, fr: tips_fr };
  const steps = buildSteps(lang, tipsMap[lang]);
  const labels = uiLabels[lang];

  const questionSteps = steps.filter(s => s.type !== 'tip');
  const totalQuestions = questionSteps.length;

  const [stepIndex, setStepIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<string, string | string[]>>({});
  const [multiBuffer, setMultiBuffer] = useState<string[]>([]);
  const [textBuffer, setTextBuffer] = useState('');
  const [formData, setFormData] = useState({ name: '', email: '', phone: '' });
  const [submitted, setSubmitted] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});

  const currentStep = steps[stepIndex];
  const isTip = currentStep.type === 'tip';

  const questionsDone = steps.slice(0, stepIndex).filter(s => s.type !== 'tip').length;
  const progress = (questionsDone / totalQuestions) * 100;

  function advance(value?: string | string[]) {
    if (!isTip) {
      const key = currentStep.id;
      const val = value ?? (currentStep.type === 'multiselect' ? multiBuffer : textBuffer);
      if (val !== undefined && val !== '' && !(Array.isArray(val) && val.length === 0)) {
        setAnswers(prev => ({ ...prev, [key]: val }));
      }
    }
    if (stepIndex < steps.length - 1) {
      setStepIndex(i => i + 1);
      setMultiBuffer([]);
      setTextBuffer('');
    }
  }

  function goBack() {
    if (stepIndex === 0) return;
    const prevStep = steps[stepIndex - 1];
    setStepIndex(i => i - 1);
    if (prevStep.type !== 'tip') {
      const prevVal = answers[prevStep.id];
      if (prevStep.type === 'multiselect' && Array.isArray(prevVal)) {
        setMultiBuffer(prevVal);
      } else if (prevStep.type === 'textarea' && typeof prevVal === 'string') {
        setTextBuffer(prevVal);
      } else {
        setMultiBuffer([]);
        setTextBuffer('');
      }
    }
  }

  function toggleMulti(value: string) {
    setMultiBuffer(prev =>
      prev.includes(value) ? prev.filter(v => v !== value) : [...prev, value]
    );
  }

  async function handleFormSubmit(e: React.FormEvent) {
    e.preventDefault();
    const errors: Record<string, string> = {};
    if (!formData.name.trim()) errors.name = labels.required;
    if (!formData.email.trim()) errors.email = labels.required;
    if (Object.keys(errors).length > 0) { setFormErrors(errors); return; }
    setFormErrors({});

    const serialized: Record<string, string> = { 'form-name': `quiz-subscribe-${lang}`, ...formData };
    for (const [k, v] of Object.entries(answers)) {
      serialized[k] = Array.isArray(v) ? v.join(',') : v;
    }
    try {
      await fetch('/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(serialized).toString(),
      });
    } catch (_) { /* fail silently */ }
    setSubmitted(true);
  }

  const result = submitted ? buildResult(answers, lang) : null;
  const whatsappMsg = encodeURIComponent(labels.whatsappMsg);
  const whatsappHref = `https://wa.me/?text=${whatsappMsg}`;

  return (
    <div className="max-w-2xl mx-auto">
      <style dangerouslySetInnerHTML={{ __html: fadeInStyle }} />

      {/* Progress bar */}
      <div className="mb-6">
        <div className="h-1 bg-jade/10 rounded-full overflow-hidden">
          <div
            className="h-full bg-jade rounded-full transition-all duration-500"
            style={{ width: `${progress}%` }}
          />
        </div>
        <p className="text-xs text-slate-mid mt-1.5 text-right">
          {labels.stepOf(questionsDone + (isTip ? 0 : 1), totalQuestions)}
        </p>
      </div>

      {!submitted && (
        <div key={currentStep.id} className="quiz-fade-in">

          {/* ── TIP ── */}
          {isTip && (
            <div className="rounded-2xl overflow-hidden border border-jade/15 bg-gradient-to-br from-jade/5 to-gold/5">
              <div className="px-6 py-5 border-b border-jade/10 flex items-center gap-3">
                <span className="text-2xl">{currentStep.tipIcon}</span>
                <span className="text-xs font-semibold text-jade uppercase tracking-widest">
                  {currentStep.tipLabel}
                </span>
              </div>
              <div className="px-6 py-6">
                <p className="text-slate leading-relaxed text-base">{currentStep.tipText}</p>
              </div>
              <div className="px-6 pb-6">
                <button
                  onClick={() => advance()}
                  className="w-full py-3 rounded-full bg-jade text-white font-medium text-sm hover:bg-jade-dark transition-colors"
                >
                  {labels.tipContinue}
                </button>
              </div>
            </div>
          )}

          {/* ── Question steps ── */}
          {!isTip && (
            <>
              {currentStep.section && (
                <span className="inline-block text-xs font-semibold px-3 py-1 rounded-full bg-jade/10 text-jade mb-4">
                  {currentStep.section}
                </span>
              )}
              <h2 className="font-serif text-2xl md:text-3xl text-slate mb-2">
                {currentStep.question}
              </h2>
              {currentStep.subtitle && (
                <p className="text-slate-mid text-sm mb-6">{currentStep.subtitle}</p>
              )}

              {/* Radio */}
              {currentStep.type === 'radio' && (
                <div className="space-y-3 mt-6">
                  {currentStep.options!.map(opt => (
                    <button
                      key={opt.value}
                      onClick={() => advance(opt.value)}
                      className="w-full flex items-center gap-4 p-4 rounded-xl border border-jade/15 bg-white text-left hover:border-jade/50 hover:shadow-sm transition-all group"
                    >
                      {opt.icon && <span className="text-xl flex-shrink-0">{opt.icon}</span>}
                      <div className="flex-1">
                        <p className="font-medium text-slate group-hover:text-jade transition-colors text-sm">{opt.label}</p>
                        {opt.sublabel && <p className="text-xs text-slate-mid mt-0.5">{opt.sublabel}</p>}
                      </div>
                      <div className="w-5 h-5 rounded-full border-2 border-jade/20 flex-shrink-0 group-hover:border-jade transition-colors" />
                    </button>
                  ))}
                </div>
              )}

              {/* Multiselect */}
              {currentStep.type === 'multiselect' && (
                <>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-6">
                    {currentStep.options!.map(opt => {
                      const selected = multiBuffer.includes(opt.value);
                      return (
                        <button
                          key={opt.value}
                          onClick={() => toggleMulti(opt.value)}
                          className={`flex flex-col items-center gap-2 p-4 rounded-xl border text-center transition-all ${
                            selected ? 'border-jade bg-jade/5 shadow-sm' : 'border-jade/15 bg-white hover:border-jade/40'
                          }`}
                          aria-pressed={selected}
                        >
                          <span className="text-2xl">{opt.icon}</span>
                          <span className="text-xs font-medium text-slate leading-tight">{opt.label}</span>
                        </button>
                      );
                    })}
                  </div>
                  <button
                    onClick={() => advance(multiBuffer)}
                    className="mt-6 w-full py-3 rounded-full bg-jade text-white font-medium text-sm hover:bg-jade-dark transition-colors"
                  >
                    {labels.next}
                  </button>
                </>
              )}

              {/* Textarea */}
              {currentStep.type === 'textarea' && (
                <>
                  <textarea
                    value={textBuffer}
                    onChange={e => setTextBuffer(e.target.value)}
                    placeholder={currentStep.placeholder}
                    rows={5}
                    className="w-full mt-6 px-4 py-3 rounded-xl border border-jade/20 bg-white text-slate placeholder-slate-mid/50 focus:outline-none focus:border-jade text-sm resize-none"
                  />
                  <button
                    onClick={() => advance(textBuffer)}
                    className="mt-4 w-full py-3 rounded-full bg-jade text-white font-medium text-sm hover:bg-jade-dark transition-colors"
                  >
                    {labels.next}
                  </button>
                </>
              )}

              {/* Lead form */}
              {currentStep.type === 'form' && (
                <form onSubmit={handleFormSubmit} className="mt-6 space-y-4" noValidate>
                  <div>
                    <label className="block text-sm font-medium text-slate mb-1.5">
                      {labels.name} <span className="text-coral">*</span>
                    </label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={e => setFormData(d => ({ ...d, name: e.target.value }))}
                      placeholder={labels.namePh}
                      className={`w-full px-4 py-3 rounded-xl border bg-white text-slate placeholder-slate-mid/50 focus:outline-none focus:border-jade text-sm ${formErrors.name ? 'border-coral' : 'border-jade/20'}`}
                    />
                    {formErrors.name && <p className="text-coral text-xs mt-1">{formErrors.name}</p>}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate mb-1.5">
                      {labels.email} <span className="text-coral">*</span>
                    </label>
                    <input
                      type="email"
                      value={formData.email}
                      onChange={e => setFormData(d => ({ ...d, email: e.target.value }))}
                      placeholder={labels.emailPh}
                      className={`w-full px-4 py-3 rounded-xl border bg-white text-slate placeholder-slate-mid/50 focus:outline-none focus:border-jade text-sm ${formErrors.email ? 'border-coral' : 'border-jade/20'}`}
                    />
                    {formErrors.email && <p className="text-coral text-xs mt-1">{formErrors.email}</p>}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate mb-1.5">
                      {labels.phone}
                    </label>
                    <input
                      type="tel"
                      value={formData.phone}
                      onChange={e => setFormData(d => ({ ...d, phone: e.target.value }))}
                      placeholder={labels.phonePh}
                      className="w-full px-4 py-3 rounded-xl border border-jade/20 bg-white text-slate placeholder-slate-mid/50 focus:outline-none focus:border-jade text-sm"
                    />
                  </div>
                  <div className="flex gap-2 items-start p-3 rounded-xl bg-jade/5 border border-jade/10">
                    <span className="text-jade text-sm mt-0.5 flex-shrink-0">ℹ</span>
                    <p className="text-xs text-slate-mid leading-relaxed">{labels.formNote}</p>
                  </div>
                  <button
                    type="submit"
                    className="w-full py-3.5 rounded-full bg-jade text-white font-semibold text-sm hover:bg-jade-dark transition-colors"
                  >
                    {labels.formCta}
                  </button>
                </form>
              )}

              {stepIndex > 0 && currentStep.type !== 'form' && (
                <button onClick={goBack} className="mt-5 text-sm text-slate-mid hover:text-jade transition-colors">
                  {labels.back}
                </button>
              )}
            </>
          )}

          {isTip && stepIndex > 0 && (
            <button onClick={goBack} className="mt-3 text-sm text-slate-mid hover:text-jade transition-colors block mx-auto">
              {labels.back}
            </button>
          )}
        </div>
      )}

      {/* ── Thank you + result ── */}
      {submitted && result && (
        <div className="quiz-fade-in mt-4 space-y-6">
          <div className="text-center py-6">
            <span className="text-4xl">✉️</span>
            <p className="mt-3 font-semibold text-slate">{labels.thanks}</p>
          </div>
          <div className="p-6 rounded-2xl bg-jade/5 border border-jade/20">
            <p className="text-xs font-semibold text-jade uppercase tracking-widest mb-2">{labels.result}</p>
            <h2 className="font-serif text-xl text-slate mb-3">{result.heading}</h2>
            <p className="text-slate-mid text-sm leading-relaxed">{result.body}</p>
            <a href={result.ctaHref} className="inline-block mt-4 text-jade font-medium text-sm hover:underline">
              {result.cta}
            </a>
          </div>
          <div className="text-center">
            <a
              href={whatsappHref}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 text-sm text-slate-mid hover:text-jade transition-colors"
            >
              <span>📱</span> {labels.whatsapp}
            </a>
          </div>
        </div>
      )}
    </div>
  );
}
