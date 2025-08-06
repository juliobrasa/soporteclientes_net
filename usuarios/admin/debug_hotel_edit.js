// Script de debugging para probar la edición de hoteles
console.log('🔧 DEBUGGING HOTEL EDIT');
console.log('=======================');

// Función de test para editar hotel
async function testEditHotel() {
    console.log('1. 🧪 Probando obtener hoteles...');
    
    try {
        const response = await fetch('admin_api.php?action=getHotels');
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        
        const result = await response.json();
        console.log('📊 Resultado getHotels:', result);
        
        if (result.success && result.hotels && result.hotels.length > 0) {
            const hotel = result.hotels[0]; // Primer hotel
            console.log('🏨 Primer hotel:', hotel);
            
            // Test 2: Probar saveHotel con datos del hotel existente
            console.log('\n2. 🧪 Probando saveHotel...');
            
            const saveData = {
                action: 'saveHotel',
                id: hotel.id,
                name: hotel.nombre_hotel,
                description: hotel.hoja_destino || '',
                website: hotel.url_booking || '',
                total_rooms: hotel.max_reviews || 200,
                status: hotel.activo ? 'active' : 'inactive'
            };
            
            console.log('📤 Datos a enviar:', saveData);
            
            const saveResponse = await fetch('admin_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saveData)
            });
            
            console.log('Save response status:', saveResponse.status);
            const saveResult = await saveResponse.json();
            console.log('📊 Resultado saveHotel:', saveResult);
            
        } else {
            console.log('❌ No hay hoteles para probar');
        }
        
    } catch (error) {
        console.error('❌ Error en test:', error);
    }
}

// Ejecutar test automáticamente
testEditHotel();

// Hacer función disponible globalmente para llamada manual
window.testEditHotel = testEditHotel;

console.log('💡 Puedes ejecutar manualmente: testEditHotel()');