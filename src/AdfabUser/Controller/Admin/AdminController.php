<?php

namespace AdfabUser\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use AdfabUser\Options\ModuleOptions;
use Zend\View\Model\ViewModel;
use Zend\Paginator\Paginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use AdfabCore\ORM\Pagination\LargeTablePaginator as ORMPaginator;

class AdminController extends AbstractActionController
{
    protected $options, $userMapper, $adminUserService;

    public function listAction()
    {
        $filter		= $this->getEvent()->getRouteMatch()->getParam('filter');
        $roleId		= $this->getEvent()->getRouteMatch()->getParam('roleId');
        $search 	= $this->params()->fromQuery('name');

        $role 		= $this->getAdminUserService()->getRoleMapper()->findByRoleId($roleId);

        $adapter = new DoctrineAdapter(new ORMPaginator($this->getAdminUserService()->getQueryUsersByRole($role, $filter, $search)));

        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage(100);
        $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));


        return new ViewModel(
            array(
                'users' => $paginator,
                'userlistElements' => $this->getOptions()->getUserListElements(),
                'filter'	=> $filter,
                'roleId' 	=> $roleId,
                'search'    => $search,
            )
        );
    }

    public function createAction()
    {
        $service = $this->getAdminUserService();
        $request = $this->getRequest();
        $form = $this->getServiceLocator()->get('adfabuseradmin_register_form');
        $form->get('submit')->setLabel('Créer');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabuser/create', array('userId' => 0)));
        $form->setAttribute('method', 'post');

        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-user/admin/user');

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $file = $this->params()->fromFiles('avatar');
            if ($file['name']) {
                $data = array_merge(
                    $data,
                    array('avatar'=> $file['name'])
                );
            }
            $user = $this->getAdminUserService()->create($data);
            if ($user) {
                $this->flashMessenger()->setNamespace('adfabuser')->addMessage('L\'utilisateur a été créé');

                return $this->redirect()->toRoute('zfcadmin/adfabuser/list');
            }
        }

        return $viewModel->setVariables(array('form' => $form,'userId' => 0));

    }

    public function editAction()
    {
        $userId = $this->getEvent()->getRouteMatch()->getParam('userId');
        if (!$userId) {
            return $this->redirect()->toRoute('zfcadmin/adfabuser/create');
        }

        $service = $this->getAdminUserService();
        $user = $service->getUserMapper()->findById($userId);

        $form = $this->getServiceLocator()->get('adfabuseradmin_register_form');
        $form->get('submit')->setLabel('Mettre à jour');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabuser/edit', array('userId' => $userId)));
        $form->setAttribute('method', 'post');

        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-user/admin/user');

        $request = $this->getRequest();

        $form->bind($user);

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $file = $this->params()->fromFiles('avatar');
            if ($file['name']) {
                $data = array_merge(
                    $data,
                    array('avatar'=> $file['name'])
                );
            }
            $result = $this->getAdminUserService()->edit($data, $user);

            if ($result) {
                return $this->redirect()->toRoute('zfcadmin/adfabuser/list');
            }
        }

        // I Do fill in the assigned role to the select element. Not that pretty. TODO : Improve this !
        $roleValue = null;

        foreach ($user->getRoles() as $id => $role) {
            $roleValue = $role->getRoleId();
        }

        if ($roleValue) {
            $roleValues = $form->get('roleId')->getValueOptions();
            $roleValues[$roleValue]['selected'] = true;
            $form->get('roleId')->setValueOptions($roleValues);
        }

        return $viewModel->setVariables(
            array(
                'form' => $form,
                'userId' => 0
            )
        );
    }

    public function removeAction()
    {
        $userId = $this->getEvent()->getRouteMatch()->getParam('userId');
        $user = $this->getUserMapper()->findById($userId);
        if ($user) {
            $this->getUserMapper()->remove($user);
            $this->flashMessenger()->setNamespace('adfabuser')->addMessage('The user was deleted');
        }

        return $this->redirect()->toRoute('zfcadmin/adfabuser/list');
    }

    public function activateAction()
    {
        $userId = $this->getEvent()->getRouteMatch()->getParam('userId');
        $user = $this->getUserMapper()->findById($userId);
        if ($user) {
            $this->getUserMapper()->activate($user);
            $this->flashMessenger()->setNamespace('adfabuser')->addMessage('The user was activated');
        }

        return $this->redirect()->toRoute('zfcadmin/adfabuser/list');
    }

    public function resetAction()
    {
        $userId = $this->getEvent()->getRouteMatch()->getParam('userId');
        $user = $this->getUserMapper()->findById($userId);
        if ($user) {
            $this->getAdminUserService()->resetPassword($user);
            $this->flashMessenger()->setNamespace('adfabuser')->addMessage('Un mail a été envoyé à '. $user->getEmail());
        }

        return $this->redirect()->toRoute('zfcadmin/adfabuser/list');
    }

    public function setOptions(ModuleOptions $options)
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions()
    {
        if (!$this->options instanceof ModuleOptions) {
            $this->setOptions($this->getServiceLocator()->get('adfabuser_module_options'));
        }

        return $this->options;
    }

    public function getUserMapper()
    {
        if (null === $this->userMapper) {
            $this->userMapper = $this->getServiceLocator()->get('zfcuser_user_mapper');
        }

        return $this->userMapper;
    }

    public function setUserMapper(UserMapperInterface $userMapper)
    {
        $this->userMapper = $userMapper;

        return $this;
    }

    public function getAdminUserService()
    {
        if (null === $this->adminUserService) {
            $this->adminUserService = $this->getServiceLocator()->get('adfabuser_user_service');
        }

        return $this->adminUserService;
    }

    public function setAdminUserService($service)
    {
        $this->adminUserService = $service;

        return $this;
    }
}
