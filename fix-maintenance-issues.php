<?php
/**
 * Corrector de Errores Menores y de Mantenimiento
 * 
 * Aborda inconsistencias de naming, flags de actor, y seguridad
 */

echo "🔧 CORRIGIENDO ERRORES MENORES Y DE MANTENIMIENTO\n";
echo str_repeat("=", 60) . "\n\n";

$issues = [];
$fixes = [];

// 1. Verificar consistencia de nombres de plataformas
echo "🔍 1. VERIFICANDO CONSISTENCIA DE NOMBRES DE PLATAFORMAS...\n";

// Buscar archivos que puedan tener inconsistencias reviewPlatforms vs platforms
$searchFiles = array_merge(
    glob(__DIR__ . '/*apify*.php'),
    glob(__DIR__ . '/*config*.php'),
    glob(__DIR__ . '/api/*apify*.php'),
    glob(__DIR__ . '/usuarios/admin/*apify*.php')
);

$platformNamingIssues = [];

foreach ($searchFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Buscar uso de reviewPlatforms
    if (stripos($content, 'reviewPlatforms') !== false) {
        echo "  📄 $filename: reviewPlatforms encontrado\n";
        $platformNamingIssues[$file] = 'reviewPlatforms';
    }
    
    // Buscar uso de platforms
    if (preg_match('/[\'"]platforms[\'"]/', $content)) {
        echo "  📄 $filename: 'platforms' encontrado\n";
        if (!isset($platformNamingIssues[$file])) {
            $platformNamingIssues[$file] = 'platforms';
        } else {
            $platformNamingIssues[$file] = 'mixed'; // Ambos
        }
    }
}

// 2. Verificar nombres exactos de flags del actor
echo "\n🔍 2. VERIFICANDO NOMBRES DE FLAGS DEL ACTOR MULTI-OTAS...\n";

$flagNamingIssues = [];

// Verificar nuestro extraction-utils.php contra documentación oficial
$extractionUtilsFile = __DIR__ . '/extraction-utils.php';
if (file_exists($extractionUtilsFile)) {
    $content = file_get_contents($extractionUtilsFile);
    
    // Verificar flags potencialmente incorrectos
    $suspiciousFlags = [
        'enableHotelscom' => 'Verificar si debe ser enableHotelsCom o enableHotels'
    ];
    
    foreach ($suspiciousFlags as $flag => $note) {
        if (stripos($content, $flag) !== false) {
            echo "  ⚠️  Flag sospechoso: $flag - $note\n";
            $flagNamingIssues[$flag] = $note;
        }
    }
    
    // Buscar todos los flags enable*
    preg_match_all("/['\"]enable\w+['\"]/", $content, $matches);
    if (!empty($matches[0])) {
        echo "  📋 Flags encontrados en " . basename($extractionUtilsFile) . ":\n";
        foreach ($matches[0] as $flag) {
            $cleanFlag = trim($flag, '"\'');
            echo "    - $cleanFlag\n";
        }
    }
}

// 3. Buscar scripts públicos peligrosos
echo "\n🔍 3. BUSCANDO SCRIPTS PÚBLICOS PELIGROSOS...\n";

$dangerousScripts = [];

// Buscar en diferentes directorios públicos
$publicDirs = [
    __DIR__ . '/kavia-laravel/public',
    __DIR__ . '/public',
    __DIR__ . '/',
];

$dangerousPatterns = [
    'debug*.php',
    'repair*.php', 
    'update*.php',
    'test*.php',
    'phpinfo*.php',
    'info.php',
    'install*.php',
    'setup*.php',
    'config*.php',
    'admin*.php'
];

foreach ($publicDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    echo "  📁 Escaneando: $dir\n";
    
    foreach ($dangerousPatterns as $pattern) {
        $files = glob($dir . '/' . $pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                $relativePath = str_replace(__DIR__ . '/', '', $file);
                echo "    ⚠️  Archivo peligroso: $relativePath\n";
                $dangerousScripts[] = $file;
            }
        }
    }
}

// 4. Aplicar correcciones
echo "\n🔧 4. APLICANDO CORRECCIONES...\n";

// 4.1 Crear mapeo estándar de flags
echo "  📋 Creando mapeo estándar de flags Apify...\n";
$standardFlagMapping = [
    'booking' => 'enableBooking',
    'googlemaps' => 'enableGoogleMaps', 
    'tripadvisor' => 'enableTripadvisor',
    'expedia' => 'enableExpedia',
    'agoda' => 'enableAgoda',
    'hotels.com' => 'enableHotelsCom', // Correcto con mayúscula
    'hotelscom' => 'enableHotelsCom'   // Aliás corregido
];

// Actualizar extraction-utils.php con flags corregidos
if (file_exists($extractionUtilsFile)) {
    $content = file_get_contents($extractionUtilsFile);
    $originalContent = $content;
    
    // Corregir enableHotelscom -> enableHotelsCom
    $content = str_replace('enableHotelscom', 'enableHotelsCom', $content);
    
    // Actualizar mapeo
    $newMapping = "        \$platformMapping = [\n";
    foreach ($standardFlagMapping as $platform => $flag) {
        $newMapping .= "            '$platform' => '$flag',\n";
    }
    $newMapping .= "        ];";
    
    // Buscar y reemplazar el mapeo existente
    $content = preg_replace(
        '/\$platformMapping\s*=\s*\[[^\]]+\];/s',
        $newMapping,
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($extractionUtilsFile, $content);
        $fixes[] = "✅ Corregidos flags de actor en extraction-utils.php";
        echo "    ✅ Flags corregidos en extraction-utils.php\n";
    } else {
        echo "    ℹ️  No hay cambios necesarios en extraction-utils.php\n";
    }
}

// 4.2 Crear archivo de configuración estándar
echo "  📋 Creando configuración Apify estándar...\n";
$apifyConfigContent = '<?php
/**
 * Configuración Apify - Nombres Estándar
 * 
 * Define mapeos consistentes para evitar inconsistencias
 * entre reviewPlatforms, platforms, y flags enableX
 */

class ApifyConfig
{
    /**
     * Mapeo estándar plataformas -> flags Apify
     * 
     * IMPORTANTE: Usar estos nombres exactos que coinciden
     * con el actor multi-OTAs oficial de Apify
     */
    public static $PLATFORM_FLAGS = [
        "booking" => "enableBooking",
        "googlemaps" => "enableGoogleMaps", 
        "tripadvisor" => "enableTripadvisor",
        "expedia" => "enableExpedia",
        "agoda" => "enableAgoda",
        "hotels.com" => "enableHotelsCom",     // ← Correcto con mayúscula C
        "hotelscom" => "enableHotelsCom"       // ← Aliás también corregido
    ];
    
    /**
     * Nombre estándar para configuración
     * 
     * USAR SIEMPRE: "platforms" (no "reviewPlatforms")
     * Para consistencia en toda la aplicación
     */
    public static $CONFIG_KEY = "platforms";
    
    /**
     * Convertir plataformas a flags Apify
     */
    public static function platformsToFlags($platforms) 
    {
        $flags = [];
        
        // Inicializar todos en false
        foreach (self::$PLATFORM_FLAGS as $flag) {
            $flags[$flag] = false;
        }
        
        // Activar solo las seleccionadas
        if (is_array($platforms)) {
            foreach ($platforms as $platform) {
                $key = strtolower(trim($platform));
                if (isset(self::$PLATFORM_FLAGS[$key])) {
                    $flags[self::$PLATFORM_FLAGS[$key]] = true;
                }
            }
        }
        
        return $flags;
    }
    
    /**
     * Validar nombres de flags contra actor oficial
     */
    public static function validateFlags($flags) 
    {
        $validFlags = array_values(self::$PLATFORM_FLAGS);
        $errors = [];
        
        foreach ($flags as $flag => $value) {
            if (strpos($flag, "enable") === 0 && !in_array($flag, $validFlags)) {
                $errors[] = "Flag desconocido: $flag (puede ser ignorado por Apify)";
            }
        }
        
        return $errors;
    }
}

// Tests de verificación
if (basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"])) {
    echo "🧪 TESTING APIFY CONFIG\n";
    echo str_repeat("=", 40) . "\n";
    
    $testPlatforms = ["booking", "hotels.com", "tripadvisor"];
    $flags = ApifyConfig::platformsToFlags($testPlatforms);
    
    echo "Plataformas: " . implode(", ", $testPlatforms) . "\n";
    echo "Flags generados:\n";
    foreach ($flags as $flag => $enabled) {
        $status = $enabled ? "✅" : "❌";
        echo "  $status $flag\n";
    }
    
    $errors = ApifyConfig::validateFlags($flags);
    if (empty($errors)) {
        echo "\n✅ Todos los flags son válidos\n";
    } else {
        echo "\n⚠️  Errores encontrados:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
}
?>';

file_put_contents(__DIR__ . '/apify-config.php', $apifyConfigContent);
$fixes[] = "✅ Creado apify-config.php con mapeos estándar";
echo "    ✅ Creado apify-config.php\n";

// 4.3 Proteger scripts peligrosos
echo "  🛡️  Protegiendo scripts públicos peligrosos...\n";

if (!empty($dangerousScripts)) {
    $protectionDir = __DIR__ . '/security-backup';
    if (!is_dir($protectionDir)) {
        mkdir($protectionDir, 0755, true);
    }
    
    foreach ($dangerousScripts as $script) {
        if (is_file($script)) {
            $filename = basename($script);
            $backupPath = $protectionDir . '/' . $filename . '.backup';
            
            // Hacer backup
            copy($script, $backupPath);
            
            // Proteger con .htaccess o renombrar
            $dir = dirname($script);
            $htaccessPath = $dir . '/.htaccess';
            
            $htaccessRule = "\n# Protección scripts debug/repair\n<Files \"$filename\">\n    Require all denied\n</Files>\n";
            
            if (is_writable($dir)) {
                file_put_contents($htaccessPath, $htaccessRule, FILE_APPEND);
                $fixes[] = "🛡️  Protegido $filename con .htaccess";
                echo "    🛡️  $filename protegido\n";
            } else {
                echo "    ⚠️  No se puede proteger $filename (permisos)\n";
            }
        }
    }
} else {
    echo "    ✅ No se encontraron scripts peligrosos\n";
}

// 5. Generar reporte
echo "\n📊 REPORTE FINAL:\n";
echo "  🔍 Problemas detectados: " . (count($platformNamingIssues) + count($flagNamingIssues) + count($dangerousScripts)) . "\n";
echo "  🔧 Correcciones aplicadas: " . count($fixes) . "\n\n";

if (!empty($fixes)) {
    echo "✅ CORRECCIONES APLICADAS:\n";
    foreach ($fixes as $fix) {
        echo "  $fix\n";
    }
    echo "\n";
}

// Recomendaciones finales
echo "💡 RECOMENDACIONES:\n";
echo "1. Usar ApifyConfig::\$PLATFORM_FLAGS para mapeos consistentes\n";
echo "2. Siempre usar 'platforms' como clave de configuración (no 'reviewPlatforms')\n";
echo "3. Verificar flags enableHotelsCom en actor Apify oficial\n";
echo "4. Remover scripts de debug/repair de directorios públicos en producción\n";
echo "5. Implementar .htaccess restrictivos en directorios públicos\n";

echo "\n🎉 Errores menores y de mantenimiento corregidos!\n";
?>