<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <main class="main" id="top">
        <div class="container-fluid">
            <div class="row min-vh-100 flex-center g-0">
                <div class="col-lg-8 col-xxl-5 py-3 position-relative">
                    <div class="card overflow-hidden z-index-1">
                        <div class="card-body p-0">
                            <div class="row g-0 h-100">
                                <div class="col-md-5 text-center bg-card-gradient">
                                    <div class="position-relative p-4 pt-md-5 pb-md-7 light">
                                        <div class="bg-holder bg-auth-card-shape" style="background-image:url(/vendor/falcon/img/icons/spot-illustrations/half-circle.png);"></div>
                                        <div class="z-index-1 relative pb-2 pt-md-5 pb-md-5">
                                            <Link class="link-light mb-4 font-sans-serif fs-4 d-inline-block fw-bolder" href="/">
                                                Shinhua
                                            </Link>
                                            <p class="opacity-75 text-white">Sistema de Gestión de Transportes</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 mb-4 mt-md-4 mb-md-5 light">
                                        <p class="text-white">¿No tienes cuenta?<br />
                                            <Link class="text-decoration-underline link-light" href="/register">Regístrate</Link>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-7 d-flex flex-center">
                                    <div class="p-4 p-md-5 flex-grow-1">
                                        <div class="row flex-between-center">
                                            <div class="col-auto">
                                                <h3>Iniciar Sesión</h3>
                                            </div>
                                        </div>
                                        
                                        <div v-if="status" class="alert alert-success mt-3" role="alert">
                                            {{ status }}
                                        </div>
                                        
                                        <form @submit.prevent="submit" class="mt-3">
                                            <div class="mb-3">
                                                <label class="form-label" for="email">Correo Electrónico</label>
                                                <input class="form-control" :class="{ 'is-invalid': form.errors.email }" id="email" type="email" v-model="form.email" autofocus required placeholder="ejemplo@correo.com" />
                                                <div class="invalid-feedback" v-if="form.errors.email">{{ form.errors.email }}</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <label class="form-label" for="password">Contraseña</label>
                                                </div>
                                                <input class="form-control" :class="{ 'is-invalid': form.errors.password }" id="password" type="password" v-model="form.password" required placeholder="Tu contraseña" />
                                                <div class="invalid-feedback" v-if="form.errors.password">{{ form.errors.password }}</div>
                                            </div>
                                            
                                            <div class="row flex-between-center">
                                                <div class="col-auto">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="remember" v-model="form.remember" />
                                                        <label class="form-check-label mb-0" for="remember">Recuérdame</label>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <Link class="fs--1" href="/forgot-password" v-if="canResetPassword">¿Olvidaste tu contraseña?</Link>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3 mt-3">
                                                <button class="btn btn-primary d-block w-100 mt-3" type="submit" :disabled="form.processing">
                                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                    Ingresar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
