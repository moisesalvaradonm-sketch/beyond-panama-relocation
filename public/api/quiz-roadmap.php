<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://beyondpanamarelocation.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit; }

$name    = htmlspecialchars(strip_tags(trim($body['name']    ?? '')), ENT_QUOTES, 'UTF-8');
$email   = trim(strtolower($body['email'] ?? ''));
$lang    = in_array($body['lang'] ?? '', ['es','en','fr']) ? $body['lang'] : 'en';
$answers = is_array($body['answers'] ?? null) ? $body['answers'] : [];

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); echo json_encode(['error' => 'Missing fields']); exit;
}

// ── Helper ────────────────────────────────────────────────────────────────────

function a(array $answers, string $key): string {
    $v = $answers[$key] ?? '';
    return is_array($v) ? implode(', ', $v) : (string)$v;
}
function aArr(array $answers, string $key): array {
    $v = $answers[$key] ?? [];
    return is_array($v) ? $v : [$v];
}

// ── Roadmap logic ─────────────────────────────────────────────────────────────

$plazo      = a($answers, 'plazo');
$proposito  = a($answers, 'proposito');
$ingresos   = a($answers, 'ingresos');
$trabajo    = a($answers, 'trabajo');
$presupuesto = a($answers, 'presupuesto_vida');
$ubicacion  = a($answers, 'ubicacion');
$destino    = a($answers, 'destino');
$familia    = aArr($answers, 'familia');
$preocupacion = aArr($answers, 'preocupacion');
$espanol    = a($answers, 'espanol');
$nacionalidad = a($answers, 'nacionalidad');
$visita     = a($answers, 'visita');
$ritmo      = a($answers, 'ritmo');

// ── Visa recommendation ───────────────────────────────────────────────────────
$isRetired  = in_array($ingresos, ['pension']) || $trabajo === 'no_trabajo';
$isRemote   = in_array($ingresos, ['remoto']) || $trabajo === 'remoto';
$isInvestor = in_array($proposito, ['inversion']) || $trabajo === 'negocio_panama'
            || in_array($presupuesto, ['4000_6000', 'mas_6000']);

$friendlyNations = in_array($nacionalidad, ['usa','canada','uk','australia','europa']);

if ($lang === 'es') {
    if ($isRetired) {
        $visaTitle = 'Visa Pensionado';
        $visaDesc  = 'La opción más popular para jubilados. Requiere ingreso mínimo de $1,000/mes (pensión o jubilación). Otorga residencia permanente y descuentos en casi todo.';
        $visaHref  = 'https://beyondpanamarelocation.com/es/visas/pensionado';
    } elseif ($isRemote && $friendlyNations) {
        $visaTitle = 'Visa Naciones Amigas o Nómada Digital';
        $visaDesc  = 'Dos rutas posibles para trabajadores remotos. Naciones Amigas da residencia permanente con vínculo económico en Panamá. Nómada Digital da 9 meses con $3,000/mes del exterior.';
        $visaHref  = 'https://beyondpanamarelocation.com/es/visas';
    } elseif ($isRemote) {
        $visaTitle = 'Visa de Nómada Digital';
        $visaDesc  = 'Ideal para trabajo remoto con ingresos del exterior. Requiere $3,000/mes comprobables. Estadía hasta 9 meses, renovable.';
        $visaHref  = 'https://beyondpanamarelocation.com/es/visas/nomada-digital';
    } elseif ($isInvestor) {
        $visaTitle = 'Inversionista Calificado';
        $visaDesc  = 'Residencia permanente en 30–45 días mediante inversión en bienes raíces ($300,000), acciones o depósito a plazo fijo. La ruta más rápida al permiso permanente.';
        $visaHref  = 'https://beyondpanamarelocation.com/es/visas/inversionista-calificado';
    } else {
        $visaTitle = 'Múltiples rutas a evaluar';
        $visaDesc  = 'Tu perfil puede calificar para varias opciones de residencia. Te recomendamos comparar Naciones Amigas, Nómada Digital y Pensionado según tu situación exacta.';
        $visaHref  = 'https://beyondpanamarelocation.com/es/visas';
    }
} elseif ($lang === 'fr') {
    if ($isRetired) {
        $visaTitle = 'Visa Pensionado';
        $visaDesc  = "L'option la plus populaire pour les retraités. Revenu minimum de 1 000 $/mois requis. Résidence permanente et réductions légales sur presque tout.";
        $visaHref  = 'https://beyondpanamarelocation.com/fr/visas/pensionado';
    } elseif ($isRemote && $friendlyNations) {
        $visaTitle = 'Nations Amies ou Nomade Digital';
        $visaDesc  = "Deux voies pour les télétravailleurs. Nations Amies offre la résidence permanente avec un lien économique au Panama. Nomade Digital : 9 mois avec 3 000 $/mois de revenus étrangers.";
        $visaHref  = 'https://beyondpanamarelocation.com/fr/visas';
    } elseif ($isRemote) {
        $visaTitle = 'Visa Nomade Digital';
        $visaDesc  = "Idéal pour le télétravail avec des revenus à l'étranger. Revenus de 3 000 $/mois requis. Séjour jusqu'à 9 mois, renouvelable.";
        $visaHref  = 'https://beyondpanamarelocation.com/fr/visas/nomade-digital';
    } elseif ($isInvestor) {
        $visaTitle = 'Investisseur Qualifié';
        $visaDesc  = "Résidence permanente en 30–45 jours via investissement immobilier (300 000 $), actions ou dépôt à terme. La voie la plus rapide vers le permis permanent.";
        $visaHref  = 'https://beyondpanamarelocation.com/fr/visas/investisseur-qualifie';
    } else {
        $visaTitle = 'Plusieurs voies à évaluer';
        $visaDesc  = "Votre profil peut être éligible à plusieurs options de résidence. Nous recommandons de comparer Nations Amies, Nomade Digital et Pensionado.";
        $visaHref  = 'https://beyondpanamarelocation.com/fr/visas';
    }
} else {
    if ($isRetired) {
        $visaTitle = 'Pensionado Visa';
        $visaDesc  = 'The most popular option for retirees. Requires a minimum income of $1,000/month (pension or retirement). Grants permanent residency and legal discounts on almost everything.';
        $visaHref  = 'https://beyondpanamarelocation.com/en/visas/pensionado';
    } elseif ($isRemote && $friendlyNations) {
        $visaTitle = 'Friendly Nations or Digital Nomad';
        $visaDesc  = 'Two possible routes for remote workers. Friendly Nations gives permanent residency with an economic tie to Panama. Digital Nomad gives 9 months with $3,000/month from abroad.';
        $visaHref  = 'https://beyondpanamarelocation.com/en/visas';
    } elseif ($isRemote) {
        $visaTitle = 'Digital Nomad Visa';
        $visaDesc  = 'Ideal for remote work with foreign-sourced income. Requires $3,000/month verifiable. Stay up to 9 months, renewable.';
        $visaHref  = 'https://beyondpanamarelocation.com/en/visas/digital-nomad';
    } elseif ($isInvestor) {
        $visaTitle = 'Qualified Investor Visa';
        $visaDesc  = 'Permanent residency in 30–45 days via real estate investment ($300,000), stocks, or fixed-term deposit. The fastest route to a permanent permit.';
        $visaHref  = 'https://beyondpanamarelocation.com/en/visas/qualified-investor';
    } else {
        $visaTitle = 'Multiple routes to evaluate';
        $visaDesc  = 'Your profile may qualify for several residency options. We recommend comparing Friendly Nations, Digital Nomad, and Pensionado based on your exact situation.';
        $visaHref  = 'https://beyondpanamarelocation.com/en/visas';
    }
}

// ── Zone recommendations ──────────────────────────────────────────────────────
$wantsBeach    = in_array($destino, ['oceano','tropical']) || $ubicacion === 'mar';
$wantsMountain = $destino === 'montanas' || $ubicacion === 'montana';
$wantsCity     = in_array($destino, ['ciudad']) || $ubicacion === 'ciudad' || $ritmo === 'urbano';
$wantsTranquil = in_array($ritmo, ['tranquilo','playa_tranquila']);
$wantsPueblo   = $destino === 'pueblo' || $ubicacion === 'pueblo';
$wantsNature   = $ubicacion === 'naturaleza';
$wantsIslands  = $destino === 'tropical';
$highBudget    = in_array($presupuesto, ['4000_6000','mas_6000']);
$lowBudget     = in_array($presupuesto, ['menos_1500','1500_2500']);

$zonePool = [
    'panama_city' => ['🏙️', ['es'=>'Ciudad de Panamá','en'=>'Panama City','fr'=>'Panama City'],       ['es'=>'Hub financiero, restaurantes de primer nivel y vuelos a 90+ destinos.','en'=>'Financial hub, world-class restaurants, and flights to 90+ destinations.','fr'=>'Hub financier, restaurants de premier niveau et vols vers 90+ destinations.']],
    'boquete'     => ['⛰️', ['es'=>'Boquete','en'=>'Boquete','fr'=>'Boquete'],                         ['es'=>'Montañas frescas, café de especialidad y comunidad expat muy activa.','en'=>'Cool highlands, specialty coffee, and one of the most active expat communities.','fr'=>'Highlands frais, café de spécialité et communauté expat très active.']],
    'coronado'    => ['🌅', ['es'=>'Coronado / Playa Farallón','en'=>'Coronado / Playa Farallón','fr'=>'Coronado / Playa Farallón'], ['es'=>'Costa Pacífica a 90 min de la capital. Golf, playa y servicios expat.','en'=>'Pacific coast 90 min from the capital. Golf, beach, and full expat services.','fr'=>'Côte Pacifique à 90 min de la capitale. Golf, plage et services expats.']],
    'bocas'       => ['🏝️', ['es'=>'Bocas del Toro','en'=>'Bocas del Toro','fr'=>'Bocas del Toro'],    ['es'=>'Islas del Caribe con ambiente bohemio y vida tropical relajada.','en'=>'Caribbean islands with bohemian vibes and a laid-back tropical lifestyle.','fr'=>'Îles des Caraïbes, ambiance bohème et vie tropicale détendue.']],
    'valle_anton' => ['🌿', ['es'=>'Valle de Antón','en'=>'Valle de Antón','fr'=>'Valle de Antón'],     ['es'=>'Pueblo tranquilo dentro de un cráter volcánico. Naturaleza y comunidad pequeña.','en'=>'Quiet town inside a volcanic crater. Nature and small-community feel.','fr'=>'Village paisible dans un cratère volcanique. Nature et ambiance de petite communauté.']],
    'pedasi'      => ['🐠', ['es'=>'Pedasí / Azuero','en'=>'Pedasí / Azuero','fr'=>'Pedasí / Azuero'], ['es'=>'Pueblo costero auténtico en la Península de Azuero, pesca y comunidad expat en crecimiento.','en'=>'Authentic coastal town on Azuero Peninsula with beach, fishing, and growing expat scene.','fr'=>'Ville côtière authentique sur la péninsule d\'Azuero — plage, pêche et communauté expat en croissance.']],
    'david'       => ['🏘️', ['es'=>'David, Chiriquí','en'=>'David, Chiriquí','fr'=>'David, Chiriquí'], ['es'=>'Ciudad práctica y asequible. Base ideal para explorar Boquete y las playas chiriquianas.','en'=>'Practical and affordable city — great base for exploring Boquete and Chiriquí beaches.','fr'=>'Ville pratique et abordable — bonne base pour explorer Boquete et les plages du Chiriquí.']],
];

$picks = [];
if ($wantsCity || $isInvestor || $highBudget) $picks[] = 'panama_city';
if ($wantsMountain || ($wantsNature && !$wantsBeach)) $picks[] = 'boquete';
if ($wantsIslands) $picks[] = 'bocas';
if ($wantsBeach && !$wantsIslands) $picks[] = 'coronado';
if ($wantsPueblo && !$wantsBeach) $picks[] = 'valle_anton';
if ($wantsTranquil && $wantsBeach && !$wantsIslands) $picks[] = 'pedasi';
if ($lowBudget && !$wantsCity) $picks[] = 'david';

$picks = array_unique($picks);
if (empty($picks)) $picks = ['panama_city','boquete','coronado'];
if (count($picks) === 1) { $picks[] = 'boquete'; if ($picks[0] !== 'coronado') $picks[] = 'coronado'; }
$picks = array_slice($picks, 0, 3);

$zones = array_map(fn($k) => [
    'emoji' => $zonePool[$k][0],
    'name'  => $zonePool[$k][1][$lang],
    'why'   => $zonePool[$k][2][$lang],
], $picks);

// ── Roadmap phases ────────────────────────────────────────────────────────────
function roadmapPhases(string $plazo, array $preocupacion, bool $isRetired, bool $isRemote, bool $isInvestor, string $lang, string $firstName): array {
    $hasVisa    = in_array('residencia', $preocupacion);
    $hasHousing = in_array('vivienda', $preocupacion);
    $hasBanking = in_array('banca', $preocupacion);
    $hasTax     = in_array('impuestos', $preocupacion);
    $hasHealth  = in_array('salud', $preocupacion);
    $hasFamily  = in_array('familia', $preocupacion);

    if ($lang === 'es') {
        switch ($plazo) {
            case '3_meses':
                return [
                    ['icon'=>'🚀', 'label'=>'Semanas 1–3: Acción inmediata',    'tasks'=>['Contratar abogado de inmigración en Panamá','Reunir documentos: pasaporte, antecedentes penales, comprobantes de ingreso','Definir zona de residencia y buscar alquiler temporal']],
                    ['icon'=>'✈️', 'label'=>'Semanas 4–7: Viaje y trámites',    'tasks'=>['Viajar a Panamá y visitar las zonas que te interesan','Abrir cuenta bancaria (proceso: 2–4 semanas)','Presentar solicitud de visa con tu abogado']],
                    ['icon'=>'🏠', 'label'=>'Semanas 8–12: Instalación',        'tasks'=>['Cerrar contrato de alquiler','Obtener SIM local y configurar servicios básicos','Inscribirte en servicios de salud y seguro']],
                ];
            case '3_6':
                return [
                    ['icon'=>'📋', 'label'=>'Mes 1–2: Preparación',             'tasks'=>['Contratar abogado de inmigración','Recopilar y apostillar documentos','Hacer un viaje de exploración a las zonas que te interesan']],
                    ['icon'=>'🏛️', 'label'=>'Mes 3–4: Trámites',               'tasks'=>['Presentar solicitud de visa','Iniciar proceso de cuenta bancaria','Definir zona y buscar opciones de vivienda']],
                    ['icon'=>'🏡', 'label'=>'Mes 5–6: Instalación',             'tasks'=>['Firmar contrato de alquiler o cierre de propiedad','Obtener cédula de residencia al aprobar la visa','Configurar servicios, salud y vida diaria']],
                ];
            case '6_12':
                return [
                    ['icon'=>'🔍', 'label'=>'Mes 1–3: Investigación y decisiones', 'tasks'=>['Contratar abogado de inmigración en Panamá','Investigar zonas: '.implode(', ', array_column([], 'name')),'Definir tipo de visa y reunir documentos iniciales']],
                    ['icon'=>'✈️', 'label'=>'Mes 4–6: Viaje de exploración',    'tasks'=>['Visitar Panamá durante 2–4 semanas','Recorrer zonas prioritarias y hacer entrevistas con agentes','Abrir cuenta bancaria durante el viaje (si ya tienes visa)']],
                    ['icon'=>'📝', 'label'=>'Mes 7–9: Trámites de residencia',  'tasks'=>['Presentar solicitud de visa con documentos completos','Evaluar opciones de vivienda en la zona elegida','Contratar seguro médico internacional']],
                    ['icon'=>'🏠', 'label'=>'Mes 10–12: Mudanza y establecimiento', 'tasks'=>['Cerrar contrato de alquiler o compra de propiedad','Obtener cédula de residencia y tarjeta de beneficios','Trasladar efectos personales y establecerte en Panamá']],
                ];
            case '1_2':
                return [
                    ['icon'=>'📚', 'label'=>'Meses 1–6: Investigación profunda', 'tasks'=>['Investigar visas, zonas y presupuesto detalladamente','Unirte a comunidades expat online (Facebook, Reddit)','Hacer un primer viaje exploratorio a Panamá']],
                    ['icon'=>'🤝', 'label'=>'Meses 7–12: Conexiones y decisiones', 'tasks'=>['Contratar abogado de inmigración','Decidir zona y tipo de residencia','Hacer segundo viaje para ver propiedades o zonas específicas']],
                    ['icon'=>'📋', 'label'=>'Meses 13–18: Trámites',            'tasks'=>['Presentar solicitud de visa','Proceso de cuenta bancaria','Buscar y cerrar vivienda']],
                    ['icon'=>'🏡', 'label'=>'Mes 19–24: Mudanza',               'tasks'=>['Instalarse en Panamá','Obtener cédula de residencia','Configurar vida cotidiana y servicios']],
                ];
            default:
                return [
                    ['icon'=>'📖', 'label'=>'Fase 1: Exploración',              'tasks'=>['Investigar las opciones de visa que mejor se adaptan a tu situación','Leer guías sobre zonas de Panamá','Unirte a comunidades expat para tener referencias de primera mano']],
                    ['icon'=>'✈️', 'label'=>'Fase 2: Viaje de reconocimiento',  'tasks'=>['Hacer un viaje de 2–4 semanas para recorrer las zonas de interés','Conocer la vida cotidiana y el costo de vida real','Reunirte con un abogado de inmigración en el país']],
                    ['icon'=>'📋', 'label'=>'Fase 3: Planificación concreta',   'tasks'=>['Definir visa, zona y presupuesto','Reunir documentos para el proceso migratorio','Establecer un cronograma realista de mudanza']],
                    ['icon'=>'🏠', 'label'=>'Fase 4: Ejecución',                'tasks'=>['Presentar solicitud de visa','Cerrar vivienda','Instalarse y obtener cédula de residencia']],
                ];
        }
    } elseif ($lang === 'fr') {
        switch ($plazo) {
            case '3_meses':
                return [
                    ['icon'=>'🚀', 'label'=>'Semaines 1–3 : Action immédiate',  'tasks'=>["Engager un avocat en immigration au Panama","Rassembler les documents : passeport, casier judiciaire, justificatifs de revenus","Définir la zone de résidence et chercher une location temporaire"]],
                    ['icon'=>'✈️', 'label'=>'Semaines 4–7 : Voyage et démarches','tasks'=>["Voyager au Panama et visiter les zones qui vous intéressent","Ouvrir un compte bancaire (processus : 2–4 semaines)","Déposer la demande de visa avec votre avocat"]],
                    ['icon'=>'🏠', 'label'=>'Semaines 8–12 : Installation',     'tasks'=>["Signer le contrat de location","Obtenir une SIM locale et configurer les services de base","S'inscrire aux services de santé et d'assurance"]],
                ];
            default:
                return [
                    ['icon'=>'📖', 'label'=>'Phase 1 : Exploration',            'tasks'=>["Rechercher les options de visa adaptées à votre situation","Lire des guides sur les zones du Panama","Rejoindre des communautés d'expatriés pour des retours d'expérience"]],
                    ['icon'=>'✈️', 'label'=>'Phase 2 : Voyage de reconnaissance','tasks'=>["Effectuer un voyage de 2 à 4 semaines pour visiter les zones d'intérêt","Découvrir le quotidien et le coût de la vie réel","Rencontrer un avocat en immigration sur place"]],
                    ['icon'=>'📋', 'label'=>'Phase 3 : Planification concrète', 'tasks'=>["Définir le visa, la zone et le budget","Rassembler les documents pour la procédure d'immigration","Établir un calendrier réaliste pour l'installation"]],
                    ['icon'=>'🏠', 'label'=>'Phase 4 : Exécution',              'tasks'=>["Déposer la demande de visa","Finaliser le logement","S'installer et obtenir la carte de résidence"]],
                ];
        }
    } else {
        switch ($plazo) {
            case '3_meses':
                return [
                    ['icon'=>'🚀', 'label'=>'Weeks 1–3: Immediate action',      'tasks'=>['Hire a Panama immigration lawyer','Gather documents: passport, criminal record, proof of income','Define your zone and look for temporary rental']],
                    ['icon'=>'✈️', 'label'=>'Weeks 4–7: Travel & paperwork',    'tasks'=>['Travel to Panama and visit the zones that interest you','Open a bank account (process takes 2–4 weeks)','File visa application with your lawyer']],
                    ['icon'=>'🏠', 'label'=>'Weeks 8–12: Settlement',           'tasks'=>['Sign rental contract','Get a local SIM and set up basic services','Register for health services and insurance']],
                ];
            case '3_6':
                return [
                    ['icon'=>'📋', 'label'=>'Month 1–2: Preparation',           'tasks'=>['Hire a Panama immigration lawyer','Gather and apostille all documents','Take an exploratory trip to areas you are considering']],
                    ['icon'=>'🏛️', 'label'=>'Month 3–4: Paperwork',            'tasks'=>['File visa application','Start bank account process','Narrow down your zone and look at housing options']],
                    ['icon'=>'🏡', 'label'=>'Month 5–6: Settlement',            'tasks'=>['Sign rental contract or close property purchase','Get residency card once visa is approved','Set up services, health, and daily life']],
                ];
            case '6_12':
                return [
                    ['icon'=>'🔍', 'label'=>'Month 1–3: Research & decisions',  'tasks'=>['Hire a Panama immigration lawyer','Research zones that match your lifestyle goals','Define visa type and gather initial documents']],
                    ['icon'=>'✈️', 'label'=>'Month 4–6: Exploratory trip',      'tasks'=>['Visit Panama for 2–4 weeks','Tour priority zones and speak with local agents','Open a bank account during the trip (if visa allows)']],
                    ['icon'=>'📝', 'label'=>'Month 7–9: Residency process',     'tasks'=>['File visa application with complete documents','Evaluate housing options in your chosen zone','Get international health insurance']],
                    ['icon'=>'🏠', 'label'=>'Month 10–12: Move & settle',       'tasks'=>['Close rental contract or property purchase','Get residency card and benefits card','Ship personal belongings and settle in Panama']],
                ];
            case '1_2':
                return [
                    ['icon'=>'📚', 'label'=>'Months 1–6: Deep research',        'tasks'=>['Deep-dive on visas, zones, and budget','Join expat communities online (Facebook, Reddit)','Take a first exploratory trip to Panama']],
                    ['icon'=>'🤝', 'label'=>'Months 7–12: Connections & decisions', 'tasks'=>['Hire a Panama immigration lawyer','Decide on zone and residency type','Take a second trip to see specific properties or areas']],
                    ['icon'=>'📋', 'label'=>'Months 13–18: Paperwork',          'tasks'=>['File visa application','Start bank account process','Find and close on housing']],
                    ['icon'=>'🏡', 'label'=>'Months 19–24: Move',               'tasks'=>['Move to Panama','Get residency card','Set up daily life and services']],
                ];
            default:
                return [
                    ['icon'=>'📖', 'label'=>'Phase 1: Exploration',             'tasks'=>['Research visa options that best fit your situation','Read guides on Panama\'s different zones','Join expat communities to hear first-hand accounts']],
                    ['icon'=>'✈️', 'label'=>'Phase 2: Reconnaissance trip',     'tasks'=>['Take a 2–4 week trip to tour areas of interest','Experience day-to-day life and real cost of living','Meet with an immigration lawyer in-country']],
                    ['icon'=>'📋', 'label'=>'Phase 3: Concrete planning',       'tasks'=>['Define visa route, zone, and budget','Gather documents for the immigration process','Set a realistic relocation timeline']],
                    ['icon'=>'🏠', 'label'=>'Phase 4: Execution',               'tasks'=>['File visa application','Close on housing','Move in and get your residency card']],
                ];
        }
    }
}

$phases = roadmapPhases($plazo, $preocupacion, $isRetired, $isRemote, $isInvestor, $lang, $name);

// ── Subject & intro copy ──────────────────────────────────────────────────────
$firstName = explode(' ', $name)[0];

if ($lang === 'es') {
    $subject      = "{$firstName}, tu Plan de Reubicación en Panamá está listo 🗺️";
    $heroTitle    = "Tu Plan de Reubicación en Panamá";
    $heroSub      = "Basado en tus respuestas del quiz — personalizado para {$firstName}";
    $introText    = "Hola {$firstName}, analizamos tus respuestas y preparamos un plan de acción real para tu mudanza a Panamá. Aquí está tu hoja de ruta personalizada.";
    $visaLabel    = "Tu ruta de visa recomendada";
    $zonesLabel   = "Zonas a considerar según tu perfil";
    $planLabel    = "Tu Plan de Acción";
    $ctaText      = "Hablar con un asesor →";
    $ctaHref      = "https://beyondpanamarelocation.com/es/contacto";
    $footerText   = "El equipo de Beyond Panama Relocation · WhatsApp: +507 6084-4256";
    $whyText      = "¿Por qué estos pasos? Los construimos en base a tu plazo, situación económica, familia y prioridades declaradas en el quiz.";
} elseif ($lang === 'fr') {
    $subject      = "{$firstName}, votre Plan de Relocalisation au Panama est prêt 🗺️";
    $heroTitle    = "Votre Plan de Relocalisation au Panama";
    $heroSub      = "Basé sur vos réponses au quiz — personnalisé pour {$firstName}";
    $introText    = "Bonjour {$firstName}, nous avons analysé vos réponses et préparé un plan d'action concret pour votre installation au Panama. Voici votre feuille de route personnalisée.";
    $visaLabel    = "Votre voie de visa recommandée";
    $zonesLabel   = "Zones à considérer selon votre profil";
    $planLabel    = "Votre Plan d'Action";
    $ctaText      = "Parler avec un conseiller →";
    $ctaHref      = "https://beyondpanamarelocation.com/fr/contacto";
    $footerText   = "L'équipe Beyond Panama Relocation · WhatsApp: +507 6084-4256";
    $whyText      = "Pourquoi ces étapes ? Nous les avons construites en fonction de votre calendrier, situation financière, famille et priorités déclarées dans le quiz.";
} else {
    $subject      = "{$firstName}, your Panama Relocation Plan is ready 🗺️";
    $heroTitle    = "Your Panama Relocation Plan";
    $heroSub      = "Based on your quiz answers — personalized for {$firstName}";
    $introText    = "Hi {$firstName}, we analyzed your answers and put together a real action plan for your move to Panama. Here is your personalized roadmap.";
    $visaLabel    = "Your recommended visa route";
    $zonesLabel   = "Areas to consider based on your profile";
    $planLabel    = "Your Action Plan";
    $ctaText      = "Talk to an advisor →";
    $ctaHref      = "https://beyondpanamarelocation.com/en/contact";
    $footerText   = "The Beyond Panama Relocation team · WhatsApp: +507 6084-4256";
    $whyText      = "Why these steps? We built them based on your timeline, financial situation, family, and priorities you shared in the quiz.";
}

// ── HTML email builder ────────────────────────────────────────────────────────
function phasesHtml(array $phases): string {
    $html = '';
    $isLast = count($phases) - 1;
    foreach ($phases as $i => $phase) {
        $tasks = '';
        foreach ($phase['tasks'] as $task) {
            $tasks .= '<tr><td style="padding:3px 0 3px 12px;font-size:14px;color:#4A6670;line-height:1.5;">• ' . htmlspecialchars($task, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        $connector = ($i < $isLast) ? '<tr><td style="padding:0;height:12px;padding-left:16px;"><div style="width:2px;height:12px;background:#E0EDEA;margin-left:8px;"></div></td></tr>' : '';
        $html .= '
        <tr>
          <td style="padding:0 0 0 0;">
            <table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:0;">
              <tr>
                <td style="width:36px;vertical-align:top;padding-top:2px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:#1A5C52;display:inline-flex;align-items:center;justify-content:center;font-size:16px;text-align:center;line-height:32px;">' . $phase['icon'] . '</div>
                </td>
                <td style="padding-left:12px;padding-bottom:16px;">
                  <p style="margin:0 0 6px 0;font-size:14px;font-weight:700;color:#1A2E35;">' . htmlspecialchars($phase['label'], ENT_QUOTES, 'UTF-8') . '</p>
                  <table cellpadding="0" cellspacing="0">
                    ' . $tasks . '
                  </table>
                </td>
              </tr>
              ' . $connector . '
            </table>
          </td>
        </tr>';
    }
    return $html;
}

function zonesHtml(array $zones): string {
    $html = '';
    foreach ($zones as $z) {
        $html .= '
        <tr>
          <td style="padding:10px 0;border-bottom:1px solid #E8F3F1;">
            <table cellpadding="0" cellspacing="0" width="100%">
              <tr>
                <td style="width:32px;font-size:20px;vertical-align:top;padding-top:2px;">' . $z['emoji'] . '</td>
                <td style="padding-left:10px;">
                  <p style="margin:0 0 2px 0;font-size:14px;font-weight:700;color:#1A2E35;">' . htmlspecialchars($z['name'], ENT_QUOTES, 'UTF-8') . '</p>
                  <p style="margin:0;font-size:13px;color:#4A6670;line-height:1.5;">' . htmlspecialchars($z['why'], ENT_QUOTES, 'UTF-8') . '</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>';
    }
    return $html;
}

$phasesHtmlStr = phasesHtml($phases);
$zonesHtmlStr  = zonesHtml($zones);

$htmlEmail = '<!DOCTYPE html>
<html lang="' . $lang . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</title>
</head>
<body style="margin:0;padding:0;background:#F4F7F6;font-family:Georgia,serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F4F7F6;padding:32px 16px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

      <!-- HEADER -->
      <tr>
        <td style="background:linear-gradient(135deg,#0B1D2A 0%,#1A5C52 100%);border-radius:16px 16px 0 0;padding:40px 40px 32px;text-align:center;">
          <p style="margin:0 0 8px 0;font-size:12px;font-weight:bold;letter-spacing:3px;color:#2DD4BF;font-family:Arial,sans-serif;text-transform:uppercase;">Beyond Panama Relocation</p>
          <h1 style="margin:0 0 8px 0;font-size:26px;color:#ffffff;font-family:Georgia,serif;line-height:1.3;">' . htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') . '</h1>
          <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.7);font-family:Arial,sans-serif;">' . htmlspecialchars($heroSub, ENT_QUOTES, 'UTF-8') . '</p>
        </td>
      </tr>

      <!-- INTRO -->
      <tr>
        <td style="background:#ffffff;padding:32px 40px 24px;">
          <p style="margin:0;font-size:15px;color:#1A2E35;line-height:1.7;font-family:Arial,sans-serif;">' . htmlspecialchars($introText, ENT_QUOTES, 'UTF-8') . '</p>
        </td>
      </tr>

      <!-- VISA RECOMMENDATION -->
      <tr>
        <td style="background:#ffffff;padding:0 40px 24px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#F0FAF8;border:1px solid #2DD4BF;border-radius:12px;padding:20px;" bgcolor="#F0FAF8">
            <tr>
              <td style="padding:20px;">
                <p style="margin:0 0 4px 0;font-size:11px;font-weight:bold;letter-spacing:2px;color:#1A5C52;font-family:Arial,sans-serif;text-transform:uppercase;">🛂 ' . htmlspecialchars($visaLabel, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:6px 0 6px 0;font-size:16px;font-weight:bold;color:#0B1D2A;font-family:Arial,sans-serif;">' . htmlspecialchars($visaTitle, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:0 0 12px 0;font-size:13px;color:#4A6670;line-height:1.6;font-family:Arial,sans-serif;">' . htmlspecialchars($visaDesc, ENT_QUOTES, 'UTF-8') . '</p>
                <a href="' . $visaHref . '" style="font-size:13px;color:#1A5C52;font-weight:bold;font-family:Arial,sans-serif;">→ ' . ($lang==='es'?'Ver más sobre esta visa':($lang==='fr'?"En savoir plus sur ce visa":'Learn more about this visa')) . '</a>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- ZONES -->
      <tr>
        <td style="background:#ffffff;padding:0 40px 24px;">
          <p style="margin:0 0 12px 0;font-size:11px;font-weight:bold;letter-spacing:2px;color:#1A5C52;font-family:Arial,sans-serif;text-transform:uppercase;">🏡 ' . htmlspecialchars($zonesLabel, ENT_QUOTES, 'UTF-8') . '</p>
          <table width="100%" cellpadding="0" cellspacing="0">
            ' . $zonesHtmlStr . '
          </table>
        </td>
      </tr>

      <!-- ACTION PLAN -->
      <tr>
        <td style="background:#ffffff;padding:0 40px 8px;">
          <p style="margin:0 0 4px 0;font-size:11px;font-weight:bold;letter-spacing:2px;color:#1A5C52;font-family:Arial,sans-serif;text-transform:uppercase;">🗺️ ' . htmlspecialchars($planLabel, ENT_QUOTES, 'UTF-8') . '</p>
          <p style="margin:0 0 16px 0;font-size:12px;color:#8A9EA6;font-family:Arial,sans-serif;">' . htmlspecialchars($whyText, ENT_QUOTES, 'UTF-8') . '</p>
          <table width="100%" cellpadding="0" cellspacing="0">
            ' . $phasesHtmlStr . '
          </table>
        </td>
      </tr>

      <!-- CTA -->
      <tr>
        <td style="background:#ffffff;padding:8px 40px 40px;text-align:center;">
          <a href="' . $ctaHref . '" style="display:inline-block;background:#1A5C52;color:#ffffff;font-size:15px;font-weight:bold;font-family:Arial,sans-serif;padding:14px 32px;border-radius:100px;text-decoration:none;">' . htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8') . '</a>
          <p style="margin:16px 0 0 0;font-size:12px;color:#8A9EA6;font-family:Arial,sans-serif;">WhatsApp: <a href="https://wa.me/50760844256" style="color:#1A5C52;">+507 6084-4256</a></p>
        </td>
      </tr>

      <!-- FOOTER -->
      <tr>
        <td style="background:#0B1D2A;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;">
          <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.5);font-family:Arial,sans-serif;">' . htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8') . '</p>
          <p style="margin:8px 0 0 0;font-size:11px;color:rgba(255,255,255,0.3);font-family:Arial,sans-serif;"><a href="https://beyondpanamarelocation.com" style="color:rgba(255,255,255,0.4);text-decoration:none;">beyondpanamarelocation.com</a></p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';

// ── Send emails ───────────────────────────────────────────────────────────────
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Beyond Panama Relocation <contact@beyondpanamarelocation.com>\r\n";
$headers .= "Reply-To: contact@beyondpanamarelocation.com\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sentClient = mail($email, $subject, $htmlEmail, $headers);

// Notify team
$teamSubject = "Quiz completado — {$name} [{$lang}] — Roadmap enviado";
$teamBody    = "Nombre: {$name}\nEmail: {$email}\nIdioma: {$lang}\n\nRespuestas del quiz:\n";
foreach ($answers as $k => $v) {
    $val = is_array($v) ? implode(', ', $v) : $v;
    $teamBody .= "{$k}: {$val}\n";
}
$teamBody .= "\nRoadmap enviado al cliente: " . ($sentClient ? 'Sí' : 'No');
$teamHeaders  = "From: contact@beyondpanamarelocation.com\r\n";
$teamHeaders .= "Reply-To: {$email}\r\n";
$teamHeaders .= "X-Mailer: PHP/" . phpversion();
mail('contact@beyondpanamarelocation.com', $teamSubject, $teamBody, $teamHeaders);

echo json_encode(['ok' => true, 'sent' => $sentClient]);
