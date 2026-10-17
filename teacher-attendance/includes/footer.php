        <!-- main content column ends here -->
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth SPA Navigation for Sidebar
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarLinks = document.querySelectorAll('#sidebarOffcanvas .nav-link, .sidebar .nav-link');
            
            sidebarLinks.forEach(link => {
                // Skip external links, kiosk or logout
                if (link.target === '_blank' || link.href.includes('logout.php') || link.href.includes('attendance-kiosk.php')) return;
                
                link.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const url = link.href;
                    
                    // Close mobile offcanvas if open
                    const offcanvasEl = document.getElementById('sidebarOffcanvas');
                    if (offcanvasEl && typeof bootstrap !== 'undefined') {
                        const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (bsOffcanvas) {
                            bsOffcanvas.hide();
                        }
                    }

                    // Prevent fetching if already on the page
                    if (window.location.href === url) return;

                    // Update active state in sidebar
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    
                    const currentMain = document.querySelector('main');
                    if (!currentMain) {
                        window.location.href = url;
                        return;
                    }
                    
                    // Slide out current content
                    currentMain.style.opacity = '0';
                    currentMain.style.transform = 'translateY(10px)';
                    currentMain.style.transition = 'all 0.2s ease-in';
                    
                    try {
                        const response = await fetch(url);
                        const html = await response.text();
                        
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMain = doc.querySelector('main');
                        
                        if (newMain) {
                            setTimeout(() => {
                                // Swap content
                                currentMain.innerHTML = newMain.innerHTML;
                                
                                // Re-evaluate scripts in the newly injected HTML
                                const scripts = currentMain.querySelectorAll('script');
                                scripts.forEach(oldScript => {
                                    const newScript = document.createElement('script');
                                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                                    oldScript.parentNode.replaceChild(newScript, oldScript);
                                });
                                
                                // Prepare for slide in
                                currentMain.style.transition = 'none';
                                currentMain.style.transform = 'translateY(-10px)';
                                
                                // Force reflow
                                void currentMain.offsetWidth;
                                
                                // Slide in new content
                                currentMain.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
                                currentMain.style.opacity = '1';
                                currentMain.style.transform = 'translateY(0)';
                                
                                // Update Browser History URL & Title
                                window.history.pushState({}, '', url);
                                if (doc.title) {
                                    document.title = doc.title;
                                }
                            }, 180);
                        } else {
                            window.location.href = url; // Fallback
                        }
                    } catch (err) {
                        window.location.href = url; // Fallback
                    }
                });
            });
            
            // Handle browser back/forward buttons
            window.addEventListener('popstate', () => {
                window.location.reload();
            });
        });
    </script>
</body>
</html>
