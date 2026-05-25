<div class="panel rounded" style="background-color: whitesmoke;">
    <div class="panel-body">
        <h5 class="text-center" style="font-size: 2.0em; color: white; font-weight: 600; margin-top: 10px; padding: 10px; border-radius: 4px; background-color: {{ initial_tab_data.color }}">{{ initial_tab_data['Status'] }}</h5>
        <div class="row" style="margin-top: 20px">
            <div class="col-md-12 col-lg-12">
                <div class="panel">
                    <div class="panel-body">
                        <h5 class="" style="text-align: center; font-size: 1.7em; font-weight: 500;">User Details</h5>
                        <hr/>
                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.35em; float: left;">Name</h5>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Name'] }}</h5>
                            </div>
                        </div>
                        <hr style="margin: 0; opacity: 0.6;"/>
                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.35em; float: left;">Mobile</h5>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Mobile'] }}</h5>
                            </div>
                        </div>
                        <hr style="margin: 0; opacity: 0.6;"/>
                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.35em; float: left;">Email</h5>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Email'] }}</h5>
                            </div>
                        </div>
                        <hr style="margin: 0; opacity: 0.6;"/>
                        <div class="row">
                                <div class="col-md-6 col-lg-6">
                                    <h5 style="font-size: 1.35em; float: left;">Role</h5>
                                </div>
                                <div class="col-md-6 col-lg-6">
                                    <h5 style="font-size: 1.4em; color: {{ initial_tab_data['group_color'] }}; font-weight: 600; float: right;">{{ initial_tab_data['Role'] }}</h5>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
