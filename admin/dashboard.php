3.	<?php
4.	require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/config.php';
5.	checkRole(1); // Solo para administradores
6.	
7.	// Obtener estadísticas
8.	$stats = [];
9.	try {
10.	    // Total de citas
11.	    $stmt = $conn->query("SELECT COUNT(*) as total FROM citas");
12.	    $stats['total_citas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
13.	    
14.	    // Citas hoy
15.	    $stmt = $conn->query("SELECT COUNT(*) as total FROM citas WHERE DATE(fecha_hora) = CURDATE()");
16.	    $stats['citas_hoy'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
17.	    
18.	    // Total pacientes
19.	    $stmt = $conn->query("SELECT COUNT(*) as total FROM pacientes");
20.	    $stats['total_pacientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
21.	    
22.	    // Total dentistas
23.	    $stmt = $conn->query("SELECT COUNT(*) as total FROM dentistas WHERE activo = 1");
24.	    $stats['total_dentistas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
25.	    
26.	} catch(PDOException $e) {
27.	    $error = "Error al obtener estadísticas: " . $e->getMessage();
28.	}
29.	
30.	require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/header.php';
31.	?>
32.	
33.	<div class="container">
34.	    <h2 class="mb-4">Panel de Administración</h2>
35.	    
36.	    <div class="row mb-4">
37.	        <div class="col-md-3">
38.	            <div class="card bg-primary text-white">
39.	                <div class="card-body">
40.	                    <h5 class="card-title">Total Citas</h5>
41.	                    <h2><?php echo $stats['total_citas']; ?></h2>
42.	                </div>
43.	            </div>
44.	        </div>
45.	        <div class="col-md-3">
46.	            <div class="card bg-success text-white">
47.	                <div class="card-body">
48.	                    <h5 class="card-title">Citas Hoy</h5>
49.	                    <h2><?php echo $stats['citas_hoy']; ?></h2>
50.	                </div>
51.	            </div>
52.	        </div>
53.	        <div class="col-md-3">
54.	            <div class="card bg-info text-white">
55.	                <div class="card-body">
56.	                    <h5 class="card-title">Pacientes</h5>
57.	                    <h2><?php echo $stats['total_pacientes']; ?></h2>
58.	                </div>
59.	            </div>
60.	        </div>
61.	        <div class="col-md-3">
62.	            <div class="card bg-warning text-dark">
63.	                <div class="card-body">
64.	                    <h5 class="card-title">Dentistas</h5>
65.	                    <h2><?php echo $stats['total_dentistas']; ?></h2>
66.	                </div>
67.	            </div>
68.	        </div>
69.	    </div>
70.	    
71.	    <div class="row">
72.	        <div class="col-md-6">
73.	            <div class="card">
74.	                <div class="card-header">
75.	                    <h5>Próximas Citas</h5>
76.	                </div>
77.	                <div class="card-body">
78.	                    <table class="table table-striped">
79.	                        <thead>
80.	                            <tr>
81.	                                <th>Paciente</th>
82.	                                <th>Fecha</th>
83.	                                <th>Servicio</th>
84.	                            </tr>
85.	                        </thead>
86.	                        <tbody>
87.	                            <?php
88.	                            $stmt = $conn->query("SELECT c.id_cita, p.nombre, p.apellido, c.fecha_hora, s.nombre_servicio 
89.	                                                 FROM citas c
90.	                                                 JOIN pacientes p ON c.id_paciente = p.id_paciente
91.	                                                 JOIN servicios s ON c.id_servicio = s.id_servicio
92.	                                                 WHERE c.fecha_hora >= NOW()
93.	                                                 ORDER BY c.fecha_hora ASC
94.	                                                 LIMIT 5");
95.	                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
96.	                                echo "<tr>
97.	                                    <td>{$row['nombre']} {$row['apellido']}</td>
98.	                                    <td>" . date('d/m/Y H:i', strtotime($row['fecha_hora'])) . "</td>
99.	                                    <td>{$row['nombre_servicio']}</td>
100.	                                </tr>";
101.	                            }
102.	                            ?>
103.	                        </tbody>
104.	                    </table>
105.	                </div>
106.	            </div>
107.	        </div>
108.	        
109.	        <div class="col-md-6">
110.	            <div class="card">
111.	                <div class="card-header">
112.	                    <h5>Últimos Pacientes Registrados</h5>
113.	                </div>
114.	                <div class="card-body">
115.	                    <table class="table table-striped">
116.	                        <thead>
117.	                            <tr>
118.	                                <th>Nombre</th>
119.	                                <th>Teléfono</th>
120.	                                <th>Fecha Registro</th>
121.	                            </tr>
122.	                        </thead>
123.	                        <tbody>
124.	                            <?php
125.	                            $stmt = $conn->query("SELECT nombre, apellido, telefono, fecha_registro 
126.	                                                 FROM pacientes 
127.	                                                 ORDER BY fecha_registro DESC 
128.	                                                 LIMIT 5");
129.	                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
130.	                                echo "<tr>
131.	                                    <td>{$row['nombre']} {$row['apellido']}</td>
132.	                                    <td>{$row['telefono']}</td>
133.	                                    <td>" . date('d/m/Y', strtotime($row['fecha_registro'])) . "</td>
134.	                                </tr>";
135.	                            }
136.	                            ?>
137.	                        </tbody>
138.	                    </table>
139.	                </div>
140.	            </div>
141.	        </div>
142.	    </div>
143.	</div>
144.	
145.	<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/consultorio_dental/includes/footer.php'; ?>
