


<nav class="bg-white border-gray-200 border-b">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">

        <a href="{{ route('home') }}" class="flex items-center space-x-3">
            <span class="self-center text-2xl font-semibold whitespace-nowrap">
                tracezilla Shopify Connector
            </span>
        </a>

        <button
            data-collapse-toggle="navbar-default"
            type="button"
            class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500  md:hidden hover:bg-gray-100"
            aria-controls="navbar-default"
            aria-expanded="false">

            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 17 14">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M1 1h15M1 7h15M1 13h15"/>
            </svg>
        </button>

        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white">
                <li>
                    <a
                        href="{{ route('home') }}"
                        class="block py-2 px-3 md:p-0
                            {{ request()->routeIs('home')
                                ? 'text-blue-700'
                                : 'text-gray-900 hover:text-blue-700' }}">
                        Home
                    </a>
                </li>

                <li>
                    <a
                        href="{{ route('shopify.test') }}"
                        class="block py-2 px-3 md:p-0
                            {{ request()->routeIs('shopify.test')
                                ? 'text-blue-700'
                                : 'text-gray-900 hover:text-blue-700' }}">
                        Shopify Test
                    </a>
                </li>


                <li>
                    <a
                        href="{{ route('tracezilla.test') }}"
                        class="block py-2 px-3 md:p-0
                            {{ request()->routeIs('tracezilla.test')
                                ? 'text-blue-700'
                                : 'text-gray-900 hover:text-blue-700' }}">
                        tracezilla Test
                    </a>
                </li>
                
                <li>
                    <a
                        href="https://github.com/Happy-bananas/tracezilla-shopify-connector"
                        target="_blank"
                        class="block py-2 px-3 md:p-0 text-gray-900 hover:text-blue-700">
                        Github
                    </a>
                </li>


                <li>
                    <a
                        href="https://happy-bananas.github.io/tracezilla-shopify-connector/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block py-2 px-3 md:p-0 text-gray-900 hover:text-blue-700">
                        Documentation
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>