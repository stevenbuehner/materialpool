<nav class="navbar navbar-expand-md navbar-light bg-faded nav-main">
    <div class="container">

        <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse"
                data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>


        <!-- Branding Image -->
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <ul class="navbar-nav mr-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="{{ route('pool.material.index') }}" role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       id="navbarMaterial">Material</a>
                    <div class="dropdown-menu" aria-labelledby="navbarMaterial">
                        <a class="dropdown-item" href="{{ route('pool.material.index') }}">Auflisten</a>
                        <a class="dropdown-item" href="{{ route('pool.material.create') }}">Erstellen</a>
                    </div>
                </li>


                <li class="nav-item">
                    <a class="nav-link" href="{{ route('pool.searchbar.index') }}">Sumaske</a>
                </li>


                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="{{ route('pool.resource.index') }}" role="button"
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                       id="navbarRessourcen">Resourcen</a>
                    <div class="dropdown-menu" aria-labelledby="navbarRessourcen">
                        <a class="dropdown-item" href="{{ route('pool.resource.index') }}">Auflisten</a>
                        <a class="dropdown-item" href="{{ route('pool.resource.create') }}">Erstellen</a>
                    </div>
                </li>


                @if (Auth::guest())
                    {{-- Login und Register if not logged in --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Register</a>
                    </li>
                @else
                    {{-- Show user and provide logout button if logged in --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                            <a class="dropdown-item"
                               href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                Logout
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                  style="display: none;">
                                {{ csrf_field() }}
                            </form>

                            <a class="dropdown-item" href="#">Settings</a>
                        </div>
                    </li>
                @endif


            </ul>

            <form class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="text" placeholder="Schnellsuche">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Suchen</button>
            </form>
        </div>


    </div>


</nav>