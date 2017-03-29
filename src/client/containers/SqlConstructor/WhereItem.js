import { connect } from 'react-redux'
import { changeEntityWhere, changePropertyWhere, setValue } from '../../redux/modules/where'
import WhereItem from '../../components/WhereItem'
import { bindAllTo } from '../../util/helpers'

const mapStateToProps = state => ({});

const mapDispatchToProps = { changeEntityWhere, changePropertyWhere, setValue };

const mergeProps = (stateProps, dispatchProps, ownProps) => {
  // Привязываем ко всем actionCreator'ам индекс текущего поля в общей форме
  return Object.assign({}, ownProps, stateProps, bindAllTo(dispatchProps, this, ownProps.indexWhere));
};

export default connect(
  mapStateToProps,
  mapDispatchToProps,
  mergeProps
)(WhereItem);
