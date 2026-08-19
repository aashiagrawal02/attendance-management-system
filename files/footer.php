    </div><!-- end .content -->
</div><!-- end .main -->
</div><!-- end .layout -->

<script>
// Auto-hide flash messages after 4 seconds
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(el) {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(function(){ el.style.display='none'; }, 500);
    });
}, 4000);
</script>

</body>
</html>
