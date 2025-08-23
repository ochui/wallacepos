<div class="row">
    <h3 id="welcome"></h3>
    <h5>This area gives you access to your purchase history and account details.</h5>
</div>
<script>
    $(function(){
       var name = POSgetUser().name;
       $("#welcome").text('Hi '+name+', Welcome to your '+POSgetConfigTable().general.bizname+' Account.');
       POSutil.hideLoader();
    });
</script>