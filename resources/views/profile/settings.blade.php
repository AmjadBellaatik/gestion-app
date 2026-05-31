<x-app-layout>

    <div class="container py-4">

        <div class="card">

            <div class="card-header">

                <h4>
                    {{ __('messages.settings') }}
                </h4>

            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="{{ route('profile.settings.update') }}"
                >

                    @csrf
                    @method('PUT')

                    <div class="mb-3">

                        <label class="form-label">
                            {{ __('messages.language') }}
                        </label>

                        <select
                            name="language"
                            class="form-select"
                        >

                            <option value="fr">
                                Français
                            </option>

                            <option value="en">
                                English
                            </option>

                            <option value="ar">
                                العربية
                            </option>

                        </select>

                    </div>

                    <div class="form-check mb-3">

                        <input
                            type="checkbox"
                            name="notifications"
                            class="form-check-input"
                            value="1"
                        >

                        <label class="form-check-label">

                            {{ __('messages.notifications') }}

                        </label>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            {{ __('messages.password') }}
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            {{ __('messages.password_confirmation') }}
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        {{ __('messages.save') }}

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>