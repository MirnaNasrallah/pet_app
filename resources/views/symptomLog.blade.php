
<script src={{ asset('js/firebase.js') }}> </script>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 m-auto">
            <div class="card">
                <div class="card-header text-center font-weight-bold">
                    <h4 class="text-danger"><strong>Add Symptom</strong></h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('sendNotification') }}" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        @csrf
                        <div class="row mb-4">
                            <div class="mx-auto my-2 col-lg-5 shadow py-3 rounded-lg mainColor">
                                <label class="font-weight-bold ml-1"> Product name </label>
                                <input type="text" class="form-control" name="name" required="required" placeholder="Enter Your Product Name">
                            </div>
                            <div class="mx-auto my-2 col-lg-5 shadow py-3 rounded-lg mainColor">
                                <label class="font-weight-bold ml-1"> severity </label>
                                <input type="text" class="form-control" name="severity" required="required" placeholder="Enter Your Product Description">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="mx-auto my-2 col-lg-5 shadow py-3 rounded-lg mainColor">
                                <label class="font-weight-bold ml-1"> pet_id </label>
                                <input type="text" class="form-control" name="pet_id" required="required" placeholder="Product Price">
                            </div>

                        </div>


                        <div class="row">
                            <button type="submit" class="site-btn m-auto" style="width: 50%;">Submit</button>
                        </div>
                    </form>
                    <a href="{{ route('logout') }}">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://www.gstatic.com/firebasejs/7.23.0/firebase.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script>

    var firebaseConfig = {
        apiKey: "AIzaSyD7cIJL2Nh4FAvFXiNpscSrCa4dqrKjkYQ",
    authDomain: "pet-app-5fbb3.firebaseapp.com",
    projectId: "pet-app-5fbb3",
    storageBucket: "pet-app-5fbb3.appspot.com",
    messagingSenderId: "173515185885",
    appId: "1:173515185885:web:32f9ace60a7e5bc9174534",
    measurementId: "G-YTSFLSTBTG"
    };

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    function initFirebaseMessagingRegistration() {
            messaging
            .requestPermission()
            .then(function () {
                return messaging.getToken()
            })
            .then(function(token) {
                console.log(token);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: '{{ route("save-token") }}',
                    type: 'POST',
                    data: {
                        token: token
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        alert('Token saved successfully.');
                    },
                    error: function (err) {
                        console.log('User Chat Token Error'+ err);
                    },
                });

            }).catch(function (err) {
                console.log('User Chat Token Error'+ err);
            });
     }

    messaging.onMessage(function(payload) {
        const noteTitle = payload.notification.title;
        const noteOptions = {
            body: payload.notification.body,
            icon: payload.notification.icon,
        };
        new Notification(noteTitle, noteOptions);
    });

</script>
