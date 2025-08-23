<div class="row">
    <h3 id="welcome"></h3>
    <h5>This area gives you access to your purchase history and account details.</h5>
</div>
<script>
    $(function(){
       var name = POS.getUser().name;
       $("#welcome").text('Hi '+name+', Welcome to your '+POS.getConfigTable().general.bizname+' Account.');
       POS.util.hideLoader();
    });
</script>