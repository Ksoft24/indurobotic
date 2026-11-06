<?php
/* Catálogo de simulaciones. Los archivos viven en sims/ (protegida: solo se sirven vía sim.php con sesión y licencia). */
function sim_categories(): array {
  return ['brazos'=>'Brazos y manipuladores', 'moviles'=>'Robots móviles', 'humanoides'=>'Humanoides y bípedos', 'celdas'=>'Celdas de trabajo'];
}
// id => [título, descripción, archivo, ícono Font Awesome, categoría]
function simulators(): array { return [
  'cobot'   => ['Cobot J6',             'Brazo colaborativo de 6 ejes con bomba de succión y 4 ventosas: traslada cajas entre dos bandas.', 'sim_cobot_J6.html',        'fa-robot',            'brazos'],
  'scara'   => ['SCARA J4',             'Celda SCARA de 4 ejes para empaque doble.',                                                     'sim_scara_J4.html',       'fa-arrows-spin',      'brazos'],
  'delta'   => ['Delta J4',             'Robot Delta de 4 ejes: celda de empaque de paquetes.',                                          'sim_delta_J4.html',       'fa-up-down-left-right','brazos'],
  'dualarm' => ['DualArm J14',          'Robot colaborativo de dos brazos (J1–J7 y J8–J14), basado en el ABB YuMi IRB 14000.',            'sim_dualarm_J14.html',    'fa-people-arrows',    'brazos'],
  'amr'     => ['AMR Muelle de Carga',  'Robot móvil con rodillos para carga y descarga en muelle, con control omnidireccional.',         'celda_transporte_amr.html','fa-truck-ramp-box',  'moviles'],
  'agv'     => ['AGV Terminal',         'AGV en una terminal de contenedores a escala real (contenedor de 40 pies).',                     'sim_avg.html',            'fa-truck-moving',     'moviles'],
  'cuadrupedo' => ['Cuadrúpedo J12',    'Robot cuadrúpedo de 12 ejes con control por teclado, marcha y poses.',                          'sim_cuadrupedo_J12.html', 'fa-paw',              'moviles'],
  'j25'     => ['Minihumanoide J25',    '25 ejes (cabeza, brazos y piernas). Control por cámara con gestos, manual, grabación y VR.',     'sim_j25.html',            'fa-child',            'humanoides'],
  'j51'     => ['Humanoide J51',        'Humanoide de 51 ejes (cintura, brazos, manos y piernas) con control por cámara o manual.',       'sim_j51.html',            'fa-person',           'humanoides'],
  'duck'    => ['Duck J15',             'Bípedo estilo Microduck de 15 ejes y 30 cm de altura máxima.',                                   'sim_microduck_J15.html',  'fa-dove',             'humanoides'],
  'qr'      => ['Clasificación QR · ARM J6',   'Celda con brazo de ventosas que lee QR con la cámara real y clasifica y paletiza.',        'celda_clasificacion_qr_arm_J6.html',        'fa-qrcode',        'celdas'],
  'inspeccion' => ['Inspección con gestos · ARM J6', 'Celda de inspección robotizada controlada por gestos de la mano (MediaPipe).',        'celda_inspeccion_gestos_arm_J6.html',       'fa-magnifying-glass','celdas'],
  'picking' => ['Picking KMR 6J',       'Robot móvil con brazo para order picking: reconoce marcadores ArUco y arma el pedido.',          'celda_picking_induRobotic_KRM_6J.html',     'fa-boxes-stacked', 'celdas'],
  'paqueteria' => ['ARM J6 · Paquetería', 'Robot de 6 ejes con ventosa de vacío para la estación de paquetería.',                         'sim_arm_j6_estacion_paqueteria.html',       'fa-box',           'celdas'],
  'pintura' => ['ARM J6 · Pintura',     'Célula de pintura con robot de 6 grados de libertad.',                                            'sim_arm_J6_estacion_pintura.html',          'fa-spray-can',     'celdas'],
]; }
