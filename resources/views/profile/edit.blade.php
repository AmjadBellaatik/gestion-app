<x-app-layout>

    <div class="container py-4">

        <div class="card">

            <div class="card-header">

                <h4>
                    {{ __('messages.profile') }}
                </h4>

            </div>

            <div class="card-body">

                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    enctype="multipart/form-data"
                >

                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                {{ __('messages.name') }}
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="{{ old('name', $user->name) }}"
                            >

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                {{ __('messages.email') }}
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="{{ old('email', $user->email) }}"
                            >

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                {{ __('messages.phone') }}
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                value="{{ old('phone', $user->phone) }}"
                            >

                        </div>

                        <div class="col-md-6 mb-3">

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

                        <div class="col-12 mb-3">

                            <label class="form-label">
                                {{ __('messages.address') }}
                            </label>

                            <textarea
                                name="address"
                                class="form-control"
                            >{{ old('address', $user->address) }}</textarea>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                {{ __('messages.password') }}
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                            >

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                {{ __('messages.password_confirmation') }}
                            </label>

                            <input
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                            >

                        </div>

                        <div class="col-12 mb-3">

                            <label class="form-label">
                                {{ __('messages.profile_picture') }}
                            </label>

                            <input
                                type="file"
                                name="profile_picture"
                                class="form-control"
                            >

                        </div>

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