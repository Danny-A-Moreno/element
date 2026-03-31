<!-- BÚSQUEDA INSTANTÁNEA CON SUGERENCIAS (OPCIONAL) -->
<script>
// Sistema de búsqueda con sugerencias en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    
    if (!searchInput) return;
    
    // Crear contenedor de sugerencias
    const suggestionsBox = document.createElement('div');
    suggestionsBox.className = 'search-suggestions';
    suggestionsBox.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        max-height: 300px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
        margin-top: 0.5rem;
    `;
    
    const searchBox = searchInput.closest('.search-box');
    searchBox.style.position = 'relative';
    searchBox.appendChild(suggestionsBox);
    
    let debounceTimer;
    
    // Búsqueda mientras escribe
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            fetch(`buscar-sugerencias.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        mostrarSugerencias(data);
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }, 300);
    });
    
    function mostrarSugerencias(sugerencias) {
        suggestionsBox.innerHTML = '';
        
        sugerencias.forEach(producto => {
            const item = document.createElement('a');
            item.href = `producto-detalle.php?id=${producto.id_producto}`;
            item.style.cssText = `
                display: flex;
                align-items: center;
                padding: 0.8rem 1rem;
                text-decoration: none;
                color: #333;
                border-bottom: 1px solid #f0f0f0;
                transition: background 0.2s ease;
            `;
            
            item.innerHTML = `
                <img src="imagenes/productos/${producto.imagen_principal || 'placeholder.png'}" 
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 1rem;"
                     onerror="this.src='imagenes/placeholder.png'">
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 0.2rem;">${producto.nombre}</div>
                    <div style="font-size: 0.9rem; color: #666;">$${Number(producto.precio).toLocaleString('es-CO')}</div>
                </div>
            `;
            
            item.addEventListener('mouseenter', function() {
                this.style.background = '#f5f5f5';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.background = 'white';
            });
            
            suggestionsBox.appendChild(item);
        });
        
        suggestionsBox.style.display = 'block';
    }
    
    // Cerrar sugerencias al hacer click fuera
    document.addEventListener('click', function(e) {
        if (!searchBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
    
    // Navegar con teclado (opcional)
    let currentIndex = -1;
    
    searchInput.addEventListener('keydown', function(e) {
        const items = suggestionsBox.querySelectorAll('a');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentIndex = (currentIndex + 1) % items.length;
            actualizarSeleccion(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            actualizarSeleccion(items);
        } else if (e.key === 'Enter' && currentIndex >= 0) {
            e.preventDefault();
            items[currentIndex].click();
        } else if (e.key === 'Escape') {
            suggestionsBox.style.display = 'none';
            currentIndex = -1;
        }
    });
    
    function actualizarSeleccion(items) {
        items.forEach((item, index) => {
            if (index === currentIndex) {
                item.style.background = '#f5f5f5';
            } else {
                item.style.background = 'white';
            }
        });
    }
});
</script>